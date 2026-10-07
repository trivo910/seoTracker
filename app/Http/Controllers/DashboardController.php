<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Website;
use App\Services\Serp\SerpProviderFactory;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $websites = Website::where('is_active', true)
            ->withCount('keywords')
            ->with(['keywords' => fn($q) => $q->with('latestRanking')])
            ->orderBy('name')
            ->get()
            ->map(function ($site) {
                $latestRanks = $site->keywords
                    ->map(fn($k) => $k->latestRanking?->rank_position)
                    ->filter()
                    ->values();

                $site->avg_rank     = $latestRanks->count() ? round($latestRanks->avg(), 1) : null;
                $site->top10_count  = $latestRanks->filter(fn($r) => $r <= 10)->count();
                $site->ranked_count = $latestRanks->count();

                return $site;
            });

        $totalWebsites  = $websites->count();
        $totalKeywords  = $websites->sum('keywords_count');
        $totalTop10     = $websites->sum('top10_count');
        $overallAvgRank = $websites->filter(fn($s) => $s->avg_rank)->avg('avg_rank');
        $overallAvgRank = $overallAvgRank ? round($overallAvgRank, 1) : null;

        $websiteTrend = $this->buildWebsiteTrendChart();

        $activeProvider = Setting::get('serp_provider') ?? config('serp.provider', 'serper');
        $providerName   = config("serp.providers.{$activeProvider}.name", ucfirst($activeProvider));

        $apiCredits = null;
        try {
            $provider   = SerpProviderFactory::make();
            $apiCredits = $provider->getRemainingCredits();
        } catch (\Exception) {}

        return view('dashboard.index', compact(
            'websites', 'totalWebsites', 'totalKeywords', 'totalTop10',
            'overallAvgRank', 'providerName', 'apiCredits', 'websiteTrend'
        ));
    }

    protected function buildWebsiteTrendChart(): array
    {
        $sites = Website::where('is_active', true)
            ->with([
                'keywords' => function ($q) {
                    $q->where('is_active', true)
                        ->with(['rankings' => function ($r) {
                            $r->select('keyword_id', 'checked_date', 'rank_position')
                                ->whereNotNull('rank_position');
                        }]);
                },
            ])
            ->orderBy('name')
            ->get();

        $ranges = [
            'monthly' => [
                'label' => 'Months',
                'dates' => collect(range(0, 11))->map(fn($i) => Carbon::today()->subMonths(11 - $i)->startOfMonth()),
                'bucket' => fn($date) => $date->copy()->startOfMonth()->format('Y-m'),
            ],
            'weekly' => [
                'label' => 'Weeks',
                'dates' => collect(range(0, 11))->map(fn($i) => Carbon::today()->subWeeks(11 - $i)->startOfWeek()),
                'bucket' => fn($date) => $date->copy()->startOfWeek()->format('Y-W'),
            ],
            'quarterly' => [
                'label' => 'Quarterly',
                'dates' => collect(range(0, 7))->map(fn($i) => Carbon::today()->subMonths((7 - $i) * 3)->startOfQuarter()),
                'bucket' => fn($date) => $date->copy()->startOfQuarter()->format('Y') . '-Q' . $date->copy()->startOfQuarter()->quarter,
            ],
        ];

        $chartData = [];

        foreach ($ranges as $key => $config) {
            $labels = $config['dates']->map(function ($date) use ($key) {
                return match ($key) {
                    'monthly' => $date->format('M Y'),
                    'weekly' => 'Wk ' . $date->weekOfYear,
                    'quarterly' => 'Q' . $date->quarter . ' ' . $date->year,
                    default => $date->format('M Y'),
                };
            })->values()->all();

            $series = $sites->map(function ($site) use ($config, $key) {
                $values = [];

                foreach ($config['dates'] as $date) {
                    $bucketKey = $config['bucket']($date);
                    $ranks = [];

                    foreach ($site->keywords as $keyword) {
                        foreach ($keyword->rankings as $ranking) {
                            $rankingDate = $ranking->checked_date instanceof Carbon
                                ? $ranking->checked_date
                                : Carbon::parse($ranking->checked_date);

                            $rankingKey = match ($key) {
                                'monthly' => $rankingDate->copy()->startOfMonth()->format('Y-m'),
                                'weekly' => $rankingDate->copy()->startOfWeek()->format('Y-W'),
                                'quarterly' => $rankingDate->copy()->startOfQuarter()->format('Y') . '-Q' . $rankingDate->copy()->startOfQuarter()->quarter,
                                default => $rankingDate->copy()->startOfMonth()->format('Y-m'),
                            };

                            if ($rankingKey === $bucketKey) {
                                $ranks[] = (int) $ranking->rank_position;
                            }
                        }
                    }

                    $values[] = $ranks ? round(array_sum($ranks) / count($ranks), 1) : null;
                }

                return [
                    'name' => $site->name,
                    'values' => $values,
                ];
            })->values()->all();

            $chartData[$key] = [
                'label' => $config['label'],
                'labels' => $labels,
                'series' => $series,
            ];
        }

        return $chartData;
    }
}
