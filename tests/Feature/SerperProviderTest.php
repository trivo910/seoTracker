<?php

namespace Tests\Feature;

use App\Services\Serp\SerperProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SerperProviderTest extends TestCase
{
    public function test_it_finds_a_domain_at_position_100(): void
    {
        $sequence = Http::fakeSequence();

        for ($page = 1; $page < 10; $page++) {
            $sequence->push(['organic' => array_map(
                fn (int $position): array => ['position' => $position, 'link' => "https://other.test/{$position}"],
                range(($page - 1) * 10 + 1, $page * 10),
            )]);
        }

        $sequence->push(['organic' => array_map(
            fn (int $position): array => [
                'position' => $position,
                'link' => $position === 100 ? 'https://www.example.com/page' : "https://other.test/{$position}",
            ],
            range(91, 100),
        )]);

        $provider = new SerperProvider('test-key');

        $this->assertSame(100, $provider->getRank('test keyword', 'https://example.com'));
        Http::assertSent(fn ($request): bool => $request['page'] === 10 && $request['num'] === 10);
    }

    public function test_it_returns_overall_rank_for_page_local_positions(): void
    {
        $sequence = Http::fakeSequence();

        for ($page = 1; $page < 7; $page++) {
            $sequence->push(['organic' => array_map(
                fn (int $position): array => ['position' => $position, 'link' => "https://other.test/{$page}/{$position}"],
                range(1, 10),
            )]);
        }

        $sequence->push(['organic' => array_map(
            fn (int $position): array => [
                'position' => $position,
                'link' => $position === 7 ? 'https://www.example.com/page' : "https://other.test/7/{$position}",
            ],
            range(1, 10),
        )]);

        $provider = new SerperProvider('test-key');

        $this->assertSame(67, $provider->getRank('test keyword', 'https://example.com'));
    }

    public function test_it_does_not_match_a_domain_suffix(): void
    {
        Http::fake([
            'google.serper.dev/search' => Http::response([
                'organic' => [
                    ['position' => 3, 'link' => 'https://notexample.com.attacker.test/page'],
                ],
            ]),
        ]);

        $provider = new SerperProvider('test-key');

        $this->assertNull($provider->getRank('test keyword', 'https://example.com'));
    }
}