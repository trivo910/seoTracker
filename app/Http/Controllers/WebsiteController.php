<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WebsiteController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'domain'   => ['required', 'string', 'max:255'],
            'base_url' => ['required', 'url', 'max:500'],
        ]);

        $data['created_by'] = auth()->id();
        $data['is_active']  = true;

        $website = Website::create($data);

        ActivityLog::record(
            userId:      auth()->id(),
            action:      'website.created',
            modelType:   'Website',
            modelId:     $website->id,
            description: "Created website: \"{$website->name}\" ({$website->domain})",
            newValues:   $website->only(['name', 'domain', 'base_url'])
        );

        return back()->with('success', "Website \"{$website->name}\" added.");
    }

    public function update(Request $request, Website $website)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'domain'    => ['required', 'string', 'max:255'],
            'base_url'  => ['required', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Checkbox sends '1' when checked, hidden sends '0'; cast to bool
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $website->update($data);

        return back()->with('success', "Website \"{$website->name}\" updated.");
    }

    public function show(Request $request, Website $website)
    {
        $days = (int) $request->get('days', 7);
        $days = in_array($days, [7, 14, 30]) ? $days : 7;

        $dateColumns = collect(range(1, $days - 1))
            ->map(fn($d) => Carbon::today()->subDays($d))
            ->reverse()
            ->values();

        $keywords = $website->keywords()
            ->with([
                'latestRanking',
                'creator',
                'rankings' => fn($q) => $q
                    ->where('checked_date', '>=', Carbon::today()->subDays($days))
                    ->orderBy('checked_date'),
            ])
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function ($kw) {
                $rankMap = $kw->rankings
                    ->keyBy(fn($r) => $r->checked_date->format('Y-m-d'))
                    ->map(fn($r) => $r->rank_position);

                $kw->rankingsMap = $rankMap;
                $kw->today_rank  = $rankMap[Carbon::today()->format('Y-m-d')] ?? null;

                $yesterday = $rankMap[Carbon::yesterday()->format('Y-m-d')] ?? null;
                $kw->rank_change = ($kw->today_rank && $yesterday)
                    ? ($kw->today_rank - $yesterday)
                    : null;

                $spark = $kw->rankings->sortBy('checked_date')->take(7);
                $sparkline = $spark->map(function ($r) {
                    if (!$r->rank_position) return 4;
                    return max(4, min(20, round(20 - ($r->rank_position / 5))));
                })->values()->toArray();

                while (count($sparkline) < 7) {
                    array_unshift($sparkline, 4);
                }
                $kw->sparkline = $sparkline;

                return $kw;
            });

        $totalKeywords = $keywords->count();
        $activeRanks   = $keywords->filter(fn($k) => $k->today_rank)->pluck('today_rank');
        $avgRank       = $activeRanks->avg() ?? 0;
        $top10Count    = $activeRanks->filter(fn($r) => $r <= 10)->count();
        $newThisWeek   = $website->keywords()->where('created_at', '>=', Carbon::now()->subWeek())->count();

        $yesterdayAvg = $keywords->map(function ($k) {
            return $k->rankingsMap[Carbon::yesterday()->format('Y-m-d')] ?? null;
        })->filter()->avg();

        $rankTrend = 'same';
        if ($avgRank && $yesterdayAvg) {
            if ($avgRank < $yesterdayAvg)     $rankTrend = 'up';
            elseif ($avgRank > $yesterdayAvg) $rankTrend = 'down';
        }

        $yesterdayTop10 = $keywords->filter(function ($k) {
            $r = $k->rankingsMap[Carbon::yesterday()->format('Y-m-d')] ?? null;
            return $r && $r <= 10;
        })->count();
        $top10Change = $top10Count - $yesterdayTop10;

        $lastChecked = KeywordRanking::whereHas('keyword', fn($q) => $q->where('website_id', $website->id))
            ->latest('checked_at')
            ->value('checked_at');

        return view('websites.show', compact(
            'website', 'keywords', 'dateColumns', 'days',
            'totalKeywords', 'avgRank', 'top10Count', 'newThisWeek',
            'rankTrend', 'top10Change', 'lastChecked'
        ));
    }
}
