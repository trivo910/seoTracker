@props(['distribution', 'website', 'days', 'selectedDate'])

<section class="bg-white rounded-xl border border-gray-200 p-5 mb-6" aria-labelledby="rank-distribution-title">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-5">
        <div>
            <h2 id="rank-distribution-title" class="font-semibold text-gray-800">
                {{ $selectedDate === now()->toDateString() ? "Today's keyword performance" : 'Keyword performance' }}
            </h2>
            <p class="text-xs text-gray-400 mt-0.5">
                {{ number_format($distribution['total']) }} keywords checked on {{ \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}
                <span class="block">Not found in results is included in 20+; unchecked keywords are excluded</span>
            </p>
        </div>
        <form method="GET" action="{{ route('websites.show', $website) }}" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="days" value="{{ $days }}">
            <div>
                <label for="rank-date" class="block text-xs font-medium text-gray-600 mb-1">Ranking date</label>
                <input id="rank-date" type="date" name="rank_date" value="{{ $selectedDate }}"
                       max="{{ now()->toDateString() }}" required
                       class="px-3 py-2 text-sm border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                Show date
            </button>
            <a href="{{ route('websites.show', [$website, 'days' => $days]) }}"
               class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900">
                Today
            </a>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
        <div class="flex flex-col items-center justify-center">
            <svg viewBox="0 0 200 200" class="w-52 h-52 max-w-full"
                 role="img" aria-label="Today's keyword ranking distribution">
                @if($distribution['total'] > 0)
                    @php
                        $radius = 72;
                        $circumference = 2 * pi() * $radius;
                        $offset = 0;
                    @endphp
                    <circle cx="100" cy="100" r="{{ $radius }}" fill="none" stroke="#f1f5f9" stroke-width="28" />
                    @foreach($distribution['buckets'] as $bucket)
                        @if($bucket['count'] > 0)
                            @php
                                $sliceLength = $circumference * ($bucket['count'] / $distribution['total']);
                                $remainingLength = $circumference - $sliceLength;
                            @endphp
                            <circle cx="100" cy="100" r="{{ $radius }}" fill="none"
                                    stroke="{{ $bucket['color'] }}" stroke-width="28"
                                    stroke-dasharray="{{ $sliceLength }} {{ $remainingLength }}"
                                    stroke-dashoffset="{{ -$offset }}"
                                    transform="rotate(-90 100 100)" tabindex="0" role="img"
                                    aria-label="{{ $bucket['label'] }}: {{ $bucket['count'] }} keywords, {{ number_format($bucket['percentage'], 1) }}%">
                                <title>{{ $bucket['label'] }}: {{ number_format($bucket['count']) }} keywords ({{ number_format($bucket['percentage'], 1) }}%)</title>
                            </circle>
                            @php $offset += $sliceLength; @endphp
                        @endif
                    @endforeach
                    <text x="100" y="96" text-anchor="middle" fill="#111827" font-size="25" font-weight="700">
                        {{ number_format($distribution['total']) }}
                    </text>
                    <text x="100" y="117" text-anchor="middle" fill="#6b7280" font-size="11">keywords</text>
                @else
                    <circle cx="100" cy="100" r="{{ 72 }}" fill="none" stroke="#e2e8f0" stroke-width="28" />
                    <text x="100" y="96" text-anchor="middle" fill="#111827" font-size="25" font-weight="700">0</text>
                    <text x="100" y="117" text-anchor="middle" fill="#6b7280" font-size="11">keywords</text>
                @endif
            </svg>
            @if($distribution['total'] === 0)
                <p class="text-xs text-gray-400 text-center mt-2">The chart will update after today's rank checks.</p>
            @endif
        </div>

        <ul class="grid grid-cols-1 gap-3" aria-label="Ranking categories">
            @foreach($distribution['buckets'] as $bucket)
                <li class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-3 h-3 rounded-sm shrink-0" style="background-color: {{ $bucket['color'] }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800">{{ $bucket['label'] }}</p>
                            <p class="text-xs text-gray-400">{{ $bucket['description'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 whitespace-nowrap">
                        {{ number_format($bucket['count']) }}
                        <span class="text-xs font-normal text-gray-500">({{ number_format($bucket['percentage'], 1) }}%)</span>
                    </p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
