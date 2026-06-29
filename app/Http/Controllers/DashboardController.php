<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Website;
use App\Services\Serp\SerpProviderFactory;

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

        $activeProvider = Setting::get('serp_provider') ?? config('serp.provider', 'serper');
        $providerName   = config("serp.providers.{$activeProvider}.name", ucfirst($activeProvider));

        $apiCredits = null;
        try {
            $provider   = SerpProviderFactory::make();
            $apiCredits = $provider->getRemainingCredits();
        } catch (\Exception) {}

        return view('dashboard.index', compact(
            'websites', 'totalWebsites', 'totalKeywords', 'totalTop10',
            'overallAvgRank', 'providerName', 'apiCredits'
        ));
    }
}
