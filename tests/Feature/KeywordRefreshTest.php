<?php

namespace Tests\Feature;

use App\Jobs\RankCheckJob;
use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class KeywordRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_queue_a_ranking_check_for_a_keyword(): void
    {
        Queue::fake();

        $manager = User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
        ]);

        $keyword = Keyword::create([
            'keyword' => 'example keyword',
            'target_url' => 'https://example.com',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('keywords.index'))
            ->assertOk()
            ->assertSee(route('keywords.refresh', $keyword), false);

        $this->from(route('keywords.index'))
            ->actingAs($manager)
            ->post(route('keywords.refresh', $keyword))
            ->assertRedirect(route('keywords.index'))
            ->assertSessionHas('success');

        Queue::assertPushed(
            RankCheckJob::class,
            fn (RankCheckJob $job): bool => $job->keywordId === $keyword->id,
        );
    }

    public function test_viewer_cannot_queue_a_keyword_ranking_check(): void
    {
        Queue::fake();

        $viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $keyword = Keyword::create([
            'keyword' => 'example keyword',
            'target_url' => 'https://example.com',
            'created_by' => $viewer->id,
            'updated_by' => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->post(route('keywords.refresh', $keyword))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_manual_check_refreshes_only_the_selected_keyword_for_today(): void
    {
        config([
            'serp.provider' => 'serper',
            'serp.api_key' => 'test-key',
        ]);
        Setting::set('serp_provider', null);
        Setting::set('serp_api_key', null);
        Http::fake([
            'google.serper.dev/search' => Http::response([
                'organic' => [
                    ['position' => 1, 'link' => 'https://example.com/page'],
                ],
            ]),
        ]);

        $manager = User::factory()->create(['role' => 'manager']);
        $selected = Keyword::create([
            'keyword' => 'selected keyword',
            'target_url' => 'https://example.com/page',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);
        $other = Keyword::create([
            'keyword' => 'other keyword',
            'target_url' => 'https://other.example/page',
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);
        KeywordRanking::create([
            'keyword_id' => $selected->id,
            'rank_position' => 45,
            'provider' => 'Serper.dev',
            'checked_date' => now()->toDateString(),
            'checked_at' => now(),
        ]);

        (new RankCheckJob($selected->id))->handle();

        Http::assertSentCount(1);
        $this->assertSame(
            1,
            KeywordRanking::where('keyword_id', $selected->id)
                ->whereDate('checked_date', now()->toDateString())
                ->value('rank_position'),
        );
        $this->assertDatabaseMissing('keyword_rankings', [
            'keyword_id' => $other->id,
        ]);
        $this->assertSame(1, KeywordRanking::where('keyword_id', $selected->id)->count());
    }
}
