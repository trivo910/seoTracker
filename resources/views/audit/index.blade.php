@extends('layouts.app')
@section('title', 'Audit Log — SEO Rank Tracker')
@section('page-title', 'Audit Log')

@section('content')

{{-- ─── Filters ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('audit.index') }}" class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
    <div class="flex flex-wrap items-end gap-3">

        <div class="flex-1 min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">User</label>
            <select name="user_id"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All users</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-40">
            <label class="block text-xs font-medium text-gray-600 mb-1">Action</label>
            <select name="action"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">All actions</option>
                @foreach([
                    'keyword.added'        => 'Keyword Added',
                    'keyword.updated'      => 'Keyword Updated',
                    'keyword.deleted'      => 'Keyword Deleted',
                    'url.updated'          => 'URL Updated',
                    'user.invited'         => 'User Invited',
                    'user.updated'         => 'User Updated',
                    'api.settings_updated' => 'API Settings Updated',
                ] as $val => $label)
                <option value="{{ $val }}" {{ request('action') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="min-w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="flex items-center gap-2 pb-px">
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                Apply filter
            </button>
            @if(request()->hasAny(['user_id','action','from','to']))
            <a href="{{ route('audit.index') }}"
               class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors whitespace-nowrap">
                Clear
            </a>
            @endif
        </div>

    </div>
</form>

{{-- ─── Log Table ───────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Activity Log</h2>
        <span class="text-sm text-gray-400">{{ $logs->total() }} entries</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Date & Time</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">User</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Action</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Description</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Changes</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                @php
                    $actionStyle = match(true) {
                        str_contains($log->action, 'added')    => ['bg-green-100 text-green-700', 'Added'],
                        str_contains($log->action, 'deleted')  => ['bg-red-100 text-red-700', 'Deleted'],
                        str_contains($log->action, 'invited')  => ['bg-blue-100 text-blue-700', 'Invited'],
                        str_contains($log->action, 'updated')  => ['bg-yellow-100 text-yellow-700', 'Updated'],
                        str_contains($log->action, 'settings') => ['bg-purple-100 text-purple-700', 'Settings'],
                        default                                 => ['bg-gray-100 text-gray-600', 'Action'],
                    };

                    $actionLabel = match($log->action) {
                        'keyword.added'        => 'Keyword Added',
                        'keyword.updated'      => 'Keyword Updated',
                        'keyword.deleted'      => 'Keyword Deleted',
                        'url.updated'          => 'URL Updated',
                        'user.invited'         => 'User Invited',
                        'user.updated'         => 'User Updated',
                        'api.settings_updated' => 'API Settings',
                        default                => ucwords(str_replace(['.', '_'], ' ', $log->action)),
                    };

                    $avatarBg = $log->user ? match($log->user->avatarColor) {
                        'blue'   => 'bg-blue-500',
                        'green'  => 'bg-emerald-500',
                        'amber'  => 'bg-amber-500',
                        'purple' => 'bg-violet-500',
                        default  => 'bg-indigo-500',
                    } : 'bg-gray-400';
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">

                    {{-- Timestamp --}}
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="text-xs font-medium text-gray-700">{{ $log->created_at->format('d M Y') }}</div>
                        <div class="text-xs text-gray-400">{{ $log->created_at->format('h:i A') }}</div>
                    </td>

                    {{-- User --}}
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full {{ $avatarBg }} flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                {{ $log->user ? $log->user->initials : '?' }}
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-700">{{ $log->user?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-400">{{ $log->user?->role ? ucfirst($log->user->role) : '' }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Action badge --}}
                    <td class="px-5 py-3.5">
                        <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full {{ $actionStyle[0] }}">
                            {{ $actionLabel }}
                        </span>
                    </td>

                    {{-- Description --}}
                    <td class="px-5 py-3.5 max-w-xs">
                        <span class="text-xs text-gray-700">{{ $log->description }}</span>
                    </td>

                    {{-- Old → New values --}}
                    <td class="px-5 py-3.5">
                        @if($log->old_values || $log->new_values)
                        <button
                            onclick="toggleChanges({{ $log->id }})"
                            class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline">
                            View changes
                        </button>
                        <div id="changes-{{ $log->id }}" class="hidden mt-2 text-xs space-y-1">
                            @if($log->old_values)
                            <div class="bg-red-50 rounded px-2 py-1">
                                <span class="font-medium text-red-600">Before:</span>
                                @foreach($log->old_values as $k => $v)
                                <span class="text-red-700 ml-1">{{ $k }}: {{ is_bool($v) ? ($v ? 'yes' : 'no') : $v }}</span>
                                @endforeach
                            </div>
                            @endif
                            @if($log->new_values)
                            <div class="bg-green-50 rounded px-2 py-1">
                                <span class="font-medium text-green-600">After:</span>
                                @foreach($log->new_values as $k => $v)
                                <span class="text-green-700 ml-1">{{ $k }}: {{ is_bool($v) ? ($v ? 'yes' : 'no') : $v }}</span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- IP --}}
                    <td class="px-5 py-3.5">
                        <span class="text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</span>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                            <p class="text-gray-500 font-medium">No activity logs found</p>
                            @if(request()->hasAny(['user_id','action','from','to']))
                            <a href="{{ route('audit.index') }}" class="text-indigo-600 text-sm hover:underline">Clear filters</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $logs->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function toggleChanges(id) {
    const el = document.getElementById('changes-' + id);
    el.classList.toggle('hidden');
}
</script>
@endpush
