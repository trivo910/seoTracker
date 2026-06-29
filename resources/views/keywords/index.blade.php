@extends('layouts.app')
@section('title', 'Keywords — SEO Rank Tracker')
@section('page-title', 'Keywords')

@section('content')

{{-- ─── Breadcrumb when filtered by website ────────────────── --}}
@if(request('website_id') && ($activeWebsite = $websites->find(request('website_id'))))
<div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
    <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
    <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
    </svg>
    <a href="{{ route('websites.show', $activeWebsite) }}" class="hover:text-indigo-600 transition-colors">
        {{ $activeWebsite->name }}
    </a>
    <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
    </svg>
    <span class="text-gray-800 font-medium">Keywords</span>
</div>
@endif

{{-- ─── Header: search + website filter + add button ──────── --}}
<div class="flex flex-wrap items-center gap-3 mb-5">

    {{-- Search --}}
    <form method="GET" action="{{ route('keywords.index') }}" class="flex items-center gap-3 flex-1 min-w-0">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Search keywords or URLs…"
                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        {{-- Website filter --}}
        <select name="website_id" onchange="this.form.submit()"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white">
            <option value="">All websites</option>
            @foreach($websites as $site)
            <option value="{{ $site->id }}" {{ request('website_id') == $site->id ? 'selected' : '' }}>
                {{ $site->name }}
            </option>
            @endforeach
        </select>

        @if(request()->hasAny(['q', 'website_id']))
        <a href="{{ route('keywords.index') }}"
           class="text-sm text-gray-400 hover:text-gray-600 whitespace-nowrap">
            Clear
        </a>
        @endif
    </form>

    @if(auth()->user()->isManager())
    <button onclick="openAddModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Add Keyword
    </button>
    @endif
</div>

{{-- ─── Keywords Table ──────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

    {{-- Table header count --}}
    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50">
        <span class="text-xs text-gray-500">{{ $keywords->total() }} keywords</span>
        @if(request('website_id') && isset($activeWebsite))
        <span class="text-xs text-indigo-600 font-medium">{{ $activeWebsite->name }}</span>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="sticky left-0 z-20 bg-white px-4 py-3 text-left text-xs font-medium text-gray-500 w-10">#</th>
                    <th class="sticky left-10 z-20 bg-white min-w-[180px] px-4 py-3 text-left text-xs font-medium text-gray-500">Keyword</th>
                    <th class="sticky left-[220px] z-20 bg-white min-w-[120px] border-r border-gray-200 px-4 py-3 text-left text-xs font-medium text-gray-500">Website</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Target URL</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Searches/mo</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Volume</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Competition</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Intent</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">KD</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Current Rank</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Added by</th>
                    @if(auth()->user()->isManager())
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($keywords as $i => $kw)
                @php
                    $rank = $kw->latestRanking?->rank_position;
                    $rankClass = !$rank ? 'bg-gray-100 text-gray-400'
                        : ($rank <= 3  ? 'bg-green-500 text-white'
                        : ($rank <= 10 ? 'bg-green-100 text-green-800'
                        : ($rank <= 20 ? 'bg-yellow-100 text-yellow-800'
                        : ($rank <= 50 ? 'bg-orange-100 text-orange-800'
                        : 'bg-red-100 text-red-800'))));

                    $compColor = match($kw->competition) {
                        'Low'    => 'bg-green-100 text-green-700',
                        'Medium' => 'bg-yellow-100 text-yellow-700',
                        'High'   => 'bg-red-100 text-red-700',
                        default  => 'bg-gray-100 text-gray-500',
                    };

                    $intentColors = [
                        'I' => ['Informational', 'bg-blue-100 text-blue-700'],
                        'T' => ['Transactional', 'bg-purple-100 text-purple-700'],
                        'N' => ['Navigational', 'bg-gray-100 text-gray-600'],
                        'C' => ['Commercial', 'bg-orange-100 text-orange-700'],
                    ];
                    $intents = $kw->intent ? array_filter(explode(',', $kw->intent)) : [];
                @endphp
                <tr class="group hover:bg-gray-50 transition-colors">
                    <td class="sticky left-0 z-10 bg-white group-hover:bg-gray-50 px-4 py-3 text-gray-400 text-xs">{{ $keywords->firstItem() + $i }}</td>

                    {{-- Keyword --}}
                    <td class="sticky left-10 z-10 bg-white group-hover:bg-gray-50 min-w-[180px] px-4 py-3">
                        <span class="font-medium text-gray-800">{{ $kw->keyword }}</span>
                    </td>

                    {{-- Website --}}
                    <td class="sticky left-[220px] z-10 bg-white group-hover:bg-gray-50 min-w-[120px] border-r border-gray-200 px-4 py-3">
                        @if($kw->website)
                        <a href="{{ route('websites.show', $kw->website) }}"
                           class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-700 hover:underline">
                            {{ $kw->website->name }}
                        </a>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- URL --}}
                    <td class="px-4 py-3 max-w-xs">
                        @if($kw->target_url)
                        <a href="{{ $kw->target_url }}" target="_blank"
                           class="text-xs text-indigo-500 hover:text-indigo-700 truncate block" title="{{ $kw->target_url }}">
                            {{ $kw->target_url }}
                        </a>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Monthly searches --}}
                    <td class="px-4 py-3 text-center text-xs text-gray-600">
                        {{ $kw->monthly_searches ? number_format($kw->monthly_searches) : '—' }}
                    </td>

                    {{-- Semrush volume --}}
                    <td class="px-4 py-3 text-center text-xs text-gray-600">
                        {{ $kw->semrush_volume ? number_format($kw->semrush_volume) : '—' }}
                    </td>

                    {{-- Competition --}}
                    <td class="px-4 py-3 text-center">
                        @if($kw->competition)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $compColor }}">
                            {{ $kw->competition }}
                        </span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Intent --}}
                    <td class="px-4 py-3 text-center">
                        @if(count($intents))
                        <div class="flex flex-wrap gap-1 justify-center">
                            @foreach($intents as $intentCode)
                            @if(isset($intentColors[$intentCode]))
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $intentColors[$intentCode][1] }}"
                                  title="{{ $intentColors[$intentCode][0] }}">{{ $intentCode }}</span>
                            @endif
                            @endforeach
                        </div>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- KD --}}
                    <td class="px-4 py-3 text-center text-xs text-gray-600">{{ $kw->kd ?? '—' }}</td>

                    {{-- Current rank --}}
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center justify-center min-w-8 h-7 px-2 rounded-md text-xs font-semibold {{ $rankClass }}">
                            {{ $rank ?? '—' }}
                        </span>
                    </td>

                    {{-- Added by --}}
                    <td class="px-4 py-3">
                        @if($kw->creator)
                        @php
                            $avatarBg = match($kw->creator->avatarColor) {
                                'blue'   => 'bg-blue-500',
                                'green'  => 'bg-emerald-500',
                                'amber'  => 'bg-amber-500',
                                'purple' => 'bg-violet-500',
                                default  => 'bg-indigo-500',
                            };
                        @endphp
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full {{ $avatarBg }} flex items-center justify-center text-white text-xs font-medium flex-shrink-0">
                                {{ $kw->creator->initials }}
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-700">{{ $kw->creator->name }}</p>
                                <p class="text-xs text-gray-400">{{ $kw->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    @if(auth()->user()->isManager())
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                onclick="openEditModal({{ $kw->id }}, '{{ addslashes($kw->keyword) }}', '{{ addslashes($kw->target_url ?? '') }}', '{{ $kw->monthly_searches }}', '{{ $kw->semrush_volume }}', '{{ $kw->competition }}', '{{ $kw->intent }}', '{{ $kw->kd }}', '{{ addslashes($kw->website?->name ?? '—') }}')"
                                class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>
                            @if(auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('keywords.destroy', $kw) }}"
                                  onsubmit="return confirm('Delete keyword \'{{ addslashes($kw->keyword) }}\'? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <p class="text-gray-500 font-medium">No keywords found</p>
                            @if(request()->hasAny(['q', 'website_id']))
                            <a href="{{ route('keywords.index') }}" class="text-indigo-600 text-sm hover:underline">Clear filters</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($keywords->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $keywords->appends(request()->query())->links() }}
    </div>
    @endif
</div>

{{-- ─── ADD KEYWORD MODAL ───────────────────────────────────── --}}
@if(auth()->user()->isManager())
<div id="add-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('add-modal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Add New Keyword</h3>
            <button onclick="document.getElementById('add-modal').classList.add('hidden')"
                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('keywords.store') }}" class="p-6 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">

                {{-- Website (required if websites exist) --}}
                @if($websites->count())
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Website <span class="text-red-500">*</span>
                    </label>
                    <select id="add-website-id" name="website_id" required
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">— Select website —</option>
                        @foreach($websites as $site)
                        <option value="{{ $site->id }}" {{ request('website_id') == $site->id ? 'selected' : '' }}>
                            {{ $site->name }} ({{ $site->domain }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Keyword <span class="text-red-500">*</span></label>
                    <input type="text" name="keyword" required placeholder="e.g. mumbai darshan bus"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Target URL <span class="text-red-500">*</span></label>
                    <input type="text" name="target_url" required placeholder="https://example.com/page"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Avg. Monthly Searches</label>
                    <input type="number" name="monthly_searches" min="0" placeholder="14800"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Semrush Volume</label>
                    <input type="number" name="semrush_volume" min="0" placeholder="110"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Competition</label>
                    <select name="competition"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">— Select —</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">KD (Keyword Difficulty)</label>
                    <input type="number" name="kd" min="0" max="100" placeholder="18"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Intent</label>
                    <div id="add-intent-group" class="flex flex-wrap gap-2">
                        @foreach(['I' => 'Informational', 'T' => 'Transactional', 'N' => 'Navigational', 'C' => 'Commercial'] as $val => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="intent[]" value="{{ $val }}" class="sr-only add-intent-check">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 text-gray-500 hover:border-indigo-300 transition-colors select-none">
                                {{ $val }} — {{ $label }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Currency</label>
                    <input type="text" name="currency" placeholder="INR" maxlength="10"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('add-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Add Keyword
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─── EDIT KEYWORD MODAL ──────────────────────────────────── --}}
<div id="edit-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('edit-modal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Edit Keyword</h3>
            <button onclick="document.getElementById('edit-modal').classList.add('hidden')"
                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-form" method="POST" action="" class="p-6 space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Website</label>
                    <input type="text" id="edit-website-name" disabled
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Keyword <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-keyword" name="keyword" required
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Target URL <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-url" name="target_url" required
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Avg. Monthly Searches</label>
                    <input type="number" id="edit-monthly" name="monthly_searches" min="0"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Semrush Volume</label>
                    <input type="number" id="edit-volume" name="semrush_volume" min="0"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Competition</label>
                    <select id="edit-competition" name="competition"
                            class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">— Select —</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">KD (Keyword Difficulty)</label>
                    <input type="number" id="edit-kd" name="kd" min="0" max="100"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Intent</label>
                    <div id="edit-intent-group" class="flex flex-wrap gap-2">
                        @foreach(['I' => 'Informational', 'T' => 'Transactional', 'N' => 'Navigational', 'C' => 'Commercial'] as $val => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="intent[]" value="{{ $val }}" class="sr-only edit-intent-check">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 text-gray-500 hover:border-indigo-300 transition-colors select-none">
                                {{ $val }} — {{ $label }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')"
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
const activeWebsiteId = '{{ request('website_id') }}';

function setIntentPill(cb) {
    const pill = cb.nextElementSibling;
    if (cb.checked) {
        pill.classList.add('bg-indigo-100', 'text-indigo-700', 'border-indigo-300');
        pill.classList.remove('text-gray-500', 'border-gray-200');
    } else {
        pill.classList.remove('bg-indigo-100', 'text-indigo-700', 'border-indigo-300');
        pill.classList.add('text-gray-500', 'border-gray-200');
    }
}

function openAddModal() {
    const sel = document.getElementById('add-website-id');
    if (sel && activeWebsiteId) sel.value = activeWebsiteId;

    document.querySelectorAll('#add-intent-group .add-intent-check').forEach(cb => {
        cb.checked = false;
        setIntentPill(cb);
    });

    document.getElementById('add-modal').classList.remove('hidden');
}

function openEditModal(id, keyword, url, monthly, volume, competition, intent, kd, websiteName) {
    document.getElementById('edit-form').action = '/keywords/' + id;
    document.getElementById('edit-website-name').value = websiteName || '—';
    document.getElementById('edit-keyword').value  = keyword;
    document.getElementById('edit-url').value      = url;
    document.getElementById('edit-monthly').value  = monthly || '';
    document.getElementById('edit-volume').value   = volume || '';
    document.getElementById('edit-kd').value       = kd || '';

    const compSel = document.getElementById('edit-competition');
    for (let o of compSel.options) o.selected = o.value === competition;

    const intentArr = intent ? intent.split(',') : [];
    document.querySelectorAll('#edit-intent-group .edit-intent-check').forEach(cb => {
        cb.checked = intentArr.includes(cb.value);
        setIntentPill(cb);
    });

    document.getElementById('edit-modal').classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.add-intent-check, .edit-intent-check').forEach(cb => {
        cb.addEventListener('change', function () { setIntentPill(this); });
    });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('add-modal')?.classList.add('hidden');
        document.getElementById('edit-modal')?.classList.add('hidden');
    }
});
</script>
@endpush
