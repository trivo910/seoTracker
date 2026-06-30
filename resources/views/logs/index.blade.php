@extends('layouts.app')
@section('title', 'Job Logs — SEO Rank Tracker')
@section('page-title', 'Job Logs')

@section('content')

{{-- ─── Filters ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('logs.index') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
    <div class="flex flex-wrap items-end gap-3">

        <div class="min-w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
            <select name="date" onchange="this.form.submit()"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                @forelse($dates as $d)
                <option value="{{ $d }}" {{ $selectedDate === $d ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($d)->format('d M Y') }}</option>
                @empty
                <option value="">No log files found</option>
                @endforelse
            </select>
        </div>

        <div class="flex-1 min-w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Search message or context…"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="flex items-center gap-2 pb-px">
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                Apply filter
            </button>
            @if(request()->hasAny(['q', 'level']) || ($selectedDate && $selectedDate !== ($dates->first() ?? null)))
            <a href="{{ route('logs.index') }}"
               class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                Clear
            </a>
            @endif
        </div>
    </div>

    {{-- Level pills --}}
    <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-100">
        <span class="text-xs font-medium text-gray-500 mr-1">Level:</span>
        @php
            $levels = [
                null      => ['All', 'bg-gray-100 text-gray-600', $counts->sum()],
                'DEBUG'   => ['Debug', 'bg-gray-100 text-gray-600', $counts->get('DEBUG', 0)],
                'INFO'    => ['Info', 'bg-blue-100 text-blue-700', $counts->get('INFO', 0)],
                'WARNING' => ['Warning', 'bg-amber-100 text-amber-700', $counts->get('WARNING', 0)],
                'ERROR'   => ['Error', 'bg-red-100 text-red-700', $counts->get('ERROR', 0)],
            ];
        @endphp
        @foreach($levels as $val => [$label, $style, $count])
        <a href="{{ route('logs.index', array_filter(['date' => $selectedDate, 'q' => $q, 'level' => $val])) }}"
           class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full transition-all
               {{ $level === $val || (!$level && !$val) ? $style . ' ring-2 ring-offset-1 ring-indigo-300' : $style . ' opacity-60 hover:opacity-100' }}">
            {{ $label }}
            <span class="opacity-70">{{ $count }}</span>
        </a>
        @endforeach
    </div>
</form>

{{-- ─── Log Console ─────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div>
            <h2 class="font-semibold text-gray-800">RankCheckJob Activity</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                @if($selectedDate)
                    {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }} · showing {{ $entries->count() }} entries (most recent first)
                @else
                    No log file available
                @endif
            </p>
        </div>
        @if($selectedDate)
        <a href="{{ route('logs.index', ['date' => $selectedDate]) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-200 hover:border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Refresh
        </a>
        @endif
    </div>

    @if($entries->isEmpty())
    <div class="px-6 py-16 text-center">
        <div class="flex flex-col items-center gap-3">
            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
            </svg>
            <p class="text-gray-500 font-medium">No log entries found</p>
            @if($q || $level)
            <a href="{{ route('logs.index', ['date' => $selectedDate]) }}" class="text-indigo-600 text-sm hover:underline">Clear filters</a>
            @endif
        </div>
    </div>
    @else
    <div class="bg-gray-900 max-h-[65vh] overflow-y-auto font-mono text-xs">
        @foreach($entries as $i => $entry)
        @php
            $levelStyle = match($entry['level']) {
                'DEBUG'   => 'text-gray-400',
                'INFO'    => 'text-blue-400',
                'WARNING' => 'text-amber-400',
                'ERROR', 'CRITICAL' => 'text-red-400',
                default   => 'text-gray-300',
            };
        @endphp
        <div class="px-5 py-2.5 border-b border-gray-800 hover:bg-gray-800/50 transition-colors {{ $i % 2 === 0 ? 'bg-gray-900' : 'bg-gray-950/40' }}">
            <div class="flex items-start gap-3">
                <span class="text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($entry['date'])->format('H:i:s') }}</span>
                <span class="font-semibold whitespace-nowrap {{ $levelStyle }}">{{ str_pad($entry['level'], 7) }}</span>
                <span class="text-gray-200 flex-1">{{ $entry['message'] }}</span>
            </div>
            @if(!empty($entry['context']))
            <div class="mt-1.5 ml-[5.5rem] flex flex-wrap gap-x-4 gap-y-1 text-gray-500">
                @foreach($entry['context'] as $k => $v)
                <span><span class="text-gray-600">{{ $k }}:</span> <span class="text-gray-400">{{ is_array($v) ? json_encode($v) : $v }}</span></span>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
