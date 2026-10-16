<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteTop10SnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_10_count_uses_the_latest_ranking_date_shown_in_the_card(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 16)->setTime(12, 0));

        $user = User::factory()->create(['role' => 'manager']);
        $website = Website::create([
            'name' => 'Example',
            'domain' => 'example.com',
            'base_url' => 'https://example.com',
            'created_by' => $user->id,
        ]);
        $topKeyword = Keyword::create([
            'website_id' => $website->id,
            'keyword' => 'top keyword',
            'target_url' => 'https://example.com/top',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $lowerKeyword = Keyword::create([
            'website_id' => $website->id,
            'keyword' => 'lower keyword',
            'target_url' => 'https://example.com/lower',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        foreach ([
            [$topKeyword, '2026-10-10', 5],
            [$lowerKeyword, '2026-10-10', 15],
            [$topKeyword, '2026-10-09', 12],
        ] as [$keyword, $date, $rank]) {
            KeywordRanking::create([
                'keyword_id' => $keyword->id,
                'rank_position' => $rank,
                'checked_date' => $date,
                'checked_at' => "{$date} 12:00:00",
            ]);
        }

        $this->actingAs($user)
            ->get(route('websites.show', $website))
            ->assertOk()
            ->assertViewHas('top10Count', 1)
            ->assertViewHas('top10Change', 1)
            ->assertViewHas('top10Date', fn ($date) => $date->toDateString() === '2026-10-10')
            ->assertSee('Rankings on 10 Oct 2026');
    }
}
