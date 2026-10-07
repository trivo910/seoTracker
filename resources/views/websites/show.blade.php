@extends('layouts.app')
@section('title', $website->name.' Rankings — SEO Rank Tracker')
@section('page-title', $website->name)

@section('content')

@php
$rankBadge = fn($r) => !$r
    ? 'bg-gray-100 text-gray-400'
    : ($r <= 3  ? 'bg-green-500 text-white'
    : ($r <= 10 ? 'bg-green-100 text-green-800'
    : ($r <= 20 ? 'bg-yellow-100 text-yellow-800'
    : ($r <= 50 ? 'bg-orange-100 text-orange-800'
    : 'bg-red-100 text-red-800'))));
@endphp

{{-- ─── Breadcrumb ──────────────────────────────────────────── --}}
<div class="flex items-center gap-2 text-sm text-gray-500 mb-5">
    <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
    </svg>
    <span class="text-gray-800 font-medium">{{ $website->name }}</span>
    <span class="text-gray-400 text-xs ml-1">{{ $website->domain }}</span>
</div>

{{-- ─── Stat Cards ─────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Keywords</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalKeywords }}</p>
        @if($newThisWeek > 0)
        <p class="text-xs text-green-600 mt-1.5 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
            </svg>
            {{ $newThisWeek }} this week
        </p>
        @else
        <p class="text-xs text-gray-400 mt-1.5">tracking active</p>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Avg. Rank</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $avgRank ? number_format($avgRank, 1) : '—' }}</p>
        <p class="text-xs mt-1.5
            {{ $rankTrend === 'up' ? 'text-green-600' : ($rankTrend === 'down' ? 'text-red-500' : 'text-gray-400') }}
            flex items-center gap-1">
            @if($rankTrend === 'up')
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                improved
            @elseif($rankTrend === 'down')
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                dropped
            @else
                — same
            @endif
        </p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Top 10 Keywords</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $top10Count }}</p>
        @if($top10Change != 0)
        <p class="text-xs mt-1.5 flex items-center gap-1 {{ $top10Change > 0 ? 'text-green-600' : 'text-red-500' }}">
            @if($top10Change > 0)
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" /></svg>
                +{{ $top10Change }} today
            @else
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" /></svg>
                {{ $top10Change }} today
            @endif
        </p>
        @else
        <p class="text-xs text-gray-400 mt-1.5">— same today</p>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Checked</p>
        @if($lastChecked)
        <p class="text-lg font-bold text-gray-900 mt-2">{{ \Carbon\Carbon::parse($lastChecked)->format('j M') }}</p>
        <p class="text-xs text-gray-400 mt-1.5">{{ \Carbon\Carbon::parse($lastChecked)->diffForHumans() }}</p>
        @else
        <p class="text-3xl font-bold text-gray-400 mt-2">—</p>
        <p class="text-xs text-gray-400 mt-1.5">Not checked yet</p>
        @endif
    </div>

</div>

<x-rank-distribution-donut
    :distribution="$rankDistribution"
    :website="$website"
    :days="$days"
    :selected-date="$rankDate->toDateString()"
/>

{{-- ─── Rankings Table ──────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div class="flex items-center gap-4">
            <h2 class="font-semibold text-gray-800">Keyword Rankings</h2>
        </div>

        <div class="flex items-center gap-3">
            {{-- Days filter --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                @foreach([7, 14, 30] as $d)
                <a href="{{ route('websites.show', [$website, 'days' => $d]) }}"
                   class="px-3 py-1 rounded-md text-xs font-medium transition-colors
                       {{ $days == $d ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $d }}d
                </a>
                @endforeach
            </div>

            {{-- Add Keyword shortcut --}}
            @if(auth()->user()->isManager())
            <a href="{{ route('keywords.index', ['website_id' => $website->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Keyword
            </a>

            {{-- Refresh --}}
            <form method="POST" action="{{ route('rankings.refresh') }}">
                @csrf
                <input type="hidden" name="website_id" value="{{ $website->id }}">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Refresh
                </button>
            </form>
            @endif

            {{-- Export --}}
            <a href="{{ route('rankings.export', ['website_id' => $website->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 w-10">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 min-w-48">Keyword</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 w-16">Today</th>
                    @foreach($dateColumns as $date)
                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 w-14">
                        {{ $date->format('j M') }}
                    </th>
                    @endforeach
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 w-20">Change</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 w-24">Trend</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($keywords as $i => $kw)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>

                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800">{{ $kw->keyword }}</div>
                        @if($kw->target_url)
                        <a href="{{ $kw->target_url }}" target="_blank"
                           class="text-xs text-indigo-500 hover:text-indigo-700 truncate block max-w-xs">
                            {{ parse_url($kw->target_url, PHP_URL_PATH) ?: '/' }}
                        </a>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center min-w-8 h-7 px-2 rounded-md text-xs font-semibold {{ $rankBadge($kw->today_rank) }}">
                            {{ $kw->today_rank ?? '—' }}
                        </span>
                    </td>

                    @foreach($dateColumns as $date)
                    @php $r = $kw->rankingsMap[$date->format('Y-m-d')] ?? null; @endphp
                    <td class="px-3 py-3 text-center">
                        <span class="text-xs {{ $r ? 'text-gray-600' : 'text-gray-300' }}">{{ $r ?? '—' }}</span>
                    </td>
                    @endforeach

                    <td class="px-4 py-3 text-center">
                        @if(is_null($kw->rank_change))
                            <span class="text-gray-300 text-xs">—</span>
                        @elseif($kw->rank_change === 0)
                            <span class="text-gray-400 text-xs">— same</span>
                        @elseif($kw->rank_change < 0)
                            <span class="text-green-600 text-xs font-medium">↑ +{{ abs($kw->rank_change) }}</span>
                        @else
                            <span class="text-red-500 text-xs font-medium">↓ -{{ $kw->rank_change }}</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-end justify-center gap-0.5 h-5">
                            @foreach($kw->sparkline as $height)
                            <div class="w-1.5 rounded-sm {{ $kw->today_rank && $kw->today_rank <= 10 ? 'bg-green-400' : ($kw->today_rank && $kw->today_rank <= 20 ? 'bg-yellow-400' : 'bg-gray-300') }}"
                                 style="height: {{ $height }}px"></div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 4 + count($dateColumns) }}" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <p class="text-gray-500 font-medium">No keywords for this website yet</p>
                            @if(auth()->user()->isManager())
                            <a href="{{ route('keywords.index', ['website_id' => $website->id]) }}"
                               class="text-indigo-600 text-sm hover:underline">Add keywords →</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
