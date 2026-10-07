@extends('layouts.app')
@section('title', 'Dashboard — SEO Rank Tracker')
@section('page-title', 'Dashboard')

@section('content')

{{-- ─── Portfolio Stat Cards ────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Websites</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalWebsites }}</p>
        <p class="text-xs text-gray-400 mt-1.5">active projects</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Keywords</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalKeywords }}</p>
        <p class="text-xs text-gray-400 mt-1.5">across all websites</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Portfolio Avg. Rank</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $overallAvgRank ?? '—' }}</p>
        <p class="text-xs text-gray-400 mt-1.5">across ranked keywords</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Top 10 Keywords</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalTop10 }}</p>
        <p class="text-xs text-gray-400 mt-1.5">across all websites</p>
    </div>

</div>

{{-- ─── Website Trend Chart ──────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h2 class="font-semibold text-gray-800">Website performance trend</h2>
            <p class="text-xs text-gray-400 mt-0.5">Average rank across active websites</p>
        </div>
        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
            @foreach(['monthly' => 'Months', 'weekly' => 'Weeks', 'quarterly' => 'Quarterly'] as $key => $label)
                <button type="button" data-chart-range="{{ $key }}" class="chart-range-btn px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $key === 'weekly' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-800' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    @if(!empty($websiteTrend) && !empty($websiteTrend['weekly']['series']))
        <div class="relative">
            <svg id="website-chart" viewBox="0 0 980 320" class="w-full h-80 overflow-visible"></svg>
        </div>
    @else
        <div class="flex items-center justify-center h-60 border border-dashed border-gray-200 rounded-xl bg-gray-50 text-sm text-gray-400">
            No ranking history yet for the selected websites.
        </div>
    @endif
</div>

{{-- ─── Websites Table ──────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div>
            <h2 class="font-semibold text-gray-800">Websites</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                {{ $providerName }}
                @if($apiCredits !== null)
                    · {{ number_format($apiCredits) }} credits left
                @else
                    · Credits unavailable
                @endif
            </p>
        </div>

        @if(auth()->user()->isManager())
        <button onclick="document.getElementById('add-website-modal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Website
        </button>
        @endif
    </div>

    @if($websites->isEmpty())
    <div class="px-6 py-16 text-center">
        <div class="flex flex-col items-center gap-3">
            <svg class="w-12 h-12 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
            <p class="text-gray-500 font-medium">No websites added yet</p>
            <p class="text-sm text-gray-400">Add your first website to start tracking keyword rankings.</p>
            @if(auth()->user()->isManager())
            <button onclick="document.getElementById('add-website-modal').classList.remove('hidden')"
                    class="mt-1 text-sm text-indigo-600 hover:underline">
                Add your first website →
            </button>
            @endif
        </div>
    </div>

    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Website</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Domain</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500">Keywords</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500">Ranked</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500">Avg. Rank</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500">Top 10</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($websites as $site)
                @php
                    $avgClass = !$site->avg_rank ? 'text-gray-400'
                        : ($site->avg_rank <= 10 ? 'text-green-600 font-semibold'
                        : ($site->avg_rank <= 20 ? 'text-yellow-600 font-semibold'
                        : 'text-red-500 font-semibold'));
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">

                    {{-- Name --}}
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $site->name }}</p>
                                @if(!$site->is_active)
                                <span class="text-xs text-red-400">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </td>

                    {{-- Domain --}}
                    <td class="px-5 py-4">
                        <a href="{{ $site->base_url }}" target="_blank"
                           class="text-xs text-indigo-500 hover:text-indigo-700 hover:underline">
                            {{ $site->domain }}
                        </a>
                    </td>

                    {{-- Keywords count --}}
                    <td class="px-5 py-4 text-center text-sm text-gray-700">
                        {{ $site->keywords_count }}
                    </td>

                    {{-- Ranked / total --}}
                    <td class="px-5 py-4 text-center text-xs text-gray-500">
                        {{ $site->ranked_count }}/{{ $site->keywords_count }}
                    </td>

                    {{-- Avg rank --}}
                    <td class="px-5 py-4 text-center text-sm {{ $avgClass }}">
                        {{ $site->avg_rank ?? '—' }}
                    </td>

                    {{-- Top 10 --}}
                    <td class="px-5 py-4 text-center">
                        @if($site->top10_count > 0)
                        <span class="inline-flex items-center justify-center min-w-8 h-6 px-2 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                            {{ $site->top10_count }}
                        </span>
                        @else
                        <span class="text-gray-300 text-xs">0</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('websites.show', $site) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700 border border-indigo-200 hover:border-indigo-300 rounded-lg hover:bg-indigo-50 transition-colors whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                Rankings
                            </a>
                            <a href="{{ route('keywords.index', ['website_id' => $site->id]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-700 border border-gray-200 hover:border-gray-300 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                                Keywords
                            </a>
                            @if(auth()->user()->isManager())
                            <button
                                onclick="openEditWebsite({{ $site->id }}, '{{ addslashes($site->name) }}', '{{ addslashes($site->domain) }}', '{{ addslashes($site->base_url) }}', {{ $site->is_active ? 'true' : 'false' }})"
                                class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@push('scripts')
<script>
    const chartData = @json($websiteTrend);
    const chartColors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6', '#ec4899'];

    function renderChart(rangeKey = 'daily') {
        const container = document.getElementById('website-chart');
        if (!container || !chartData || !chartData[rangeKey]) return;

        const config = chartData[rangeKey];
        const labels = config.labels || [];
        const series = config.series || [];

        const width = 980;
        const height = 320;
        const padding = { top: 20, right: 20, bottom: 34, left: 48 };

        const allValues = series.flatMap(item => item.values.filter(v => v !== null && v !== undefined));
        const maxValue = allValues.length ? Math.max(...allValues) : 1;
        const yMax = Math.max(10, Math.ceil(maxValue) + 2);
        const plotWidth = width - padding.left - padding.right;
        const plotHeight = height - padding.top - padding.bottom;

        if (!labels.length || !series.length) {
            container.innerHTML = '';
            return;
        }

        const groupWidth = plotWidth / labels.length;
        const barWidth = Math.min(18, Math.max(10, (groupWidth / Math.max(1, series.length + 1)) * 0.8));

        const yTicks = Array.from({ length: 5 }, (_, idx) => {
            const value = yMax - ((yMax) / 4) * idx;
            const y = padding.top + plotHeight - (idx / 4) * plotHeight;
            return `
                <line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" stroke="#e5e7eb" stroke-dasharray="4 4" />
                <text x="${padding.left - 10}" y="${y + 4}" fill="#6b7280" font-size="10" text-anchor="end">${Math.round(value)}</text>
            `;
        }).join('');

        const xTicks = labels.map((label, idx) => {
            const x = padding.left + (idx * groupWidth) + (groupWidth / 2);
            return `<text x="${x}" y="${height - 8}" fill="#6b7280" font-size="10" text-anchor="middle">${label}</text>`;
        }).join('');

        const bars = labels.map((label, idx) => {
            const groupX = padding.left + (idx * groupWidth) + 10;
            return series.map((item, seriesIndex) => {
                const value = item.values[idx];
                if (value === null || value === undefined) return '';
                const x = groupX + (seriesIndex * (barWidth + 4));
                const barHeight = (value / yMax) * plotHeight;
                const y = padding.top + plotHeight - barHeight;
                const color = chartColors[seriesIndex % chartColors.length];
                return `<rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="4" fill="${color}" opacity="0.9"></rect>`;
            }).join('');
        }).join('');

        const legend = series.map((item, index) => {
            const color = chartColors[index % chartColors.length];
            return `
                <g transform="translate(${padding.left + index * 180}, 12)">
                    <rect width="10" height="10" rx="2" fill="${color}" />
                    <text x="16" y="9" fill="#374151" font-size="11">${item.name}</text>
                </g>
            `;
        }).join('');

        container.innerHTML = `
            <g>
                ${yTicks}
                <line x1="${padding.left}" y1="${padding.top}" x2="${padding.left}" y2="${height - padding.bottom}" stroke="#d1d5db" />
                <line x1="${padding.left}" y1="${height - padding.bottom}" x2="${width - padding.right}" y2="${height - padding.bottom}" stroke="#d1d5db" />
                ${bars}
                ${xTicks}
                ${legend}
            </g>
        `;
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderChart('weekly');

        document.querySelectorAll('.chart-range-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.chart-range-btn').forEach(btn => {
                    btn.classList.toggle('bg-white', btn === button);
                    btn.classList.toggle('text-indigo-700', btn === button);
                    btn.classList.toggle('shadow-sm', btn === button);
                    btn.classList.toggle('text-gray-600', btn !== button);
                    btn.classList.toggle('hover:text-gray-800', btn !== button);
                    btn.classList.toggle('bg-gray-50', btn !== button);
                });
                renderChart(button.dataset.chartRange);
            });
        });
    });
</script>
@endpush

{{-- ─── ADD WEBSITE MODAL ───────────────────────────────────── --}}
@if(auth()->user()->isManager())
<div id="add-website-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Add Website</h3>
            <button onclick="document.getElementById('add-website-modal').classList.add('hidden')"
                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('websites.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Website Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required placeholder="e.g. Mumbai Darshan Bus"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <p class="text-xs text-gray-400 mt-1">A friendly name for this website</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Domain <span class="text-red-500">*</span>
                </label>
                <input type="text" name="domain" required placeholder="e.g. mumbaidarshanbusplaces.com"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Base URL <span class="text-red-500">*</span>
                </label>
                <input type="url" name="base_url" required placeholder="https://mumbaidarshanbusplaces.com"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('add-website-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Add Website
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─── EDIT WEBSITE MODAL ──────────────────────────────────── --}}
<div id="edit-website-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Edit Website</h3>
            <button onclick="document.getElementById('edit-website-modal').classList.add('hidden')"
                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="edit-website-form" method="POST" action="" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Website Name <span class="text-red-500">*</span></label>
                <input type="text" id="ew-name" name="name" required
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Domain <span class="text-red-500">*</span></label>
                <input type="text" id="ew-domain" name="domain" required
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Base URL <span class="text-red-500">*</span></label>
                <input type="url" id="ew-url" name="base_url" required
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-700">Status</span>
                {{-- Hidden input ensures is_active=0 is sent when checkbox unchecked --}}
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="ew-active" name="is_active" value="1"
                           class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-600">Active</span>
                </label>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('edit-website-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function openEditWebsite(id, name, domain, baseUrl, isActive) {
    document.getElementById('edit-website-form').action = '/websites/' + id;
    document.getElementById('ew-name').value   = name;
    document.getElementById('ew-domain').value = domain;
    document.getElementById('ew-url').value    = baseUrl;
    document.getElementById('ew-active').checked = isActive;
    document.getElementById('edit-website-modal').classList.remove('hidden');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('add-website-modal')?.classList.add('hidden');
        document.getElementById('edit-website-modal')?.classList.add('hidden');
    }
});
</script>
@endpush
