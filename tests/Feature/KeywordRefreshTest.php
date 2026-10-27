<?php

namespace Tests\Feature;

use App\Jobs\RankCheckJob;
use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Setting;
use App\Models\User;
use App\Models\Website;
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
            ->assertDontSee(route('keywords.refresh', $keyword), false);

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

    public function test_website_refresh_only_calls_api_for_that_websites_active_keywords(): void
    {
        $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $websites = [];
        foreach (['selected', 'other'] as $name) {
            $websites[] = Website::create(['name' => $name, 'domain' => $name.'.test',
                'base_url' => 'https://'.$name.'.test', 'created_by' => $user->id]);
        }
        $keywords = [];
        foreach ([$websites[0]->id, $websites[1]->id, null, $websites[0]->id] as $i => $websiteId) {
            $keywords[] = Keyword::create(['website_id' => $websiteId, 'keyword' => 'keyword '.$i,
                'target_url' => 'https://example.com', 'created_by' => $user->id,
                'updated_by' => $user->id, 'is_active' => $i !== 3]);
        }
        Queue::fake();
        $this->actingAs($user)->from(route('websites.show', $websites[0]))
            ->post(route('rankings.refresh'), ['website_id' => $websites[0]->id])
            ->assertRedirect(route('websites.show', $websites[0]));
        $job = null;
        Queue::assertPushed(RankCheckJob::class, function ($queued) use (&$job, $websites) {
            $job = $queued;
            return $queued->websiteId === $websites[0]->id && $queued->keywordId === null;
        });
        Http::fake(['google.serper.dev/search' => Http::response(['organic' => [['link' => 'https://example.com']]])]);
        $job->handle();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['q'] === 'keyword 0');
        $this->assertDatabaseHas('keyword_rankings', ['keyword_id' => $keywords[0]->id]);
        foreach (array_slice($keywords, 1) as $keyword) {
            $this->assertDatabaseMissing('keyword_rankings', ['keyword_id' => $keyword->id]);
        }
    }

    public function test_invalid_website_cannot_queue_a_global_refresh(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))
            ->post(route('rankings.refresh'), ['website_id' => 999999])
            ->assertSessionHasErrors('website_id');
        Queue::assertNothingPushed();
    }

    public function test_refresh_without_website_preserves_global_scope(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_active' => true]))
            ->post(route('rankings.refresh'))->assertSessionHas('success');
        Queue::assertPushed(RankCheckJob::class, fn ($job) => $job->websiteId === null && $job->keywordId === null);
    }

}
