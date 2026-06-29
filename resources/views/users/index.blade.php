@extends('layouts.app')
@section('title', 'Users — SEO Rank Tracker')
@section('page-title', 'Users')

@section('content')

{{-- ─── Header ──────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-5">
    <p class="text-sm text-gray-500">Manage team members and their access levels.</p>
    <button onclick="document.getElementById('invite-modal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
        </svg>
        Invite User
    </button>
</div>

{{-- ─── Users Table ─────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50">
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">User</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500">Role</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-medium text-gray-500">Last Login</th>
                    <th class="px-5 py-3 text-center text-xs font-medium text-gray-500">Logins</th>
                    <th class="px-5 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                @php
                    $avatarBg = match($user->avatarColor) {
                        'blue'   => 'bg-blue-500',
                        'green'  => 'bg-emerald-500',
                        'amber'  => 'bg-amber-500',
                        'purple' => 'bg-violet-500',
                        default  => 'bg-indigo-500',
                    };

                    $roleBadge = match($user->role) {
                        'admin'   => 'bg-purple-100 text-purple-700 border border-purple-200',
                        'manager' => 'bg-blue-100 text-blue-700 border border-blue-200',
                        default   => 'bg-gray-100 text-gray-600 border border-gray-200',
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">

                    {{-- Avatar + Name --}}
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full {{ $avatarBg }} flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                                {{ $user->initials }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-800 flex items-center gap-1.5">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                    <span class="text-xs text-gray-400">(you)</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400">Added {{ $user->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td class="px-5 py-4">
                        <span class="text-sm text-gray-600">{{ $user->email }}</span>
                    </td>

                    {{-- Role --}}
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex text-xs font-medium px-2.5 py-1 rounded-full {{ $roleBadge }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="px-5 py-4 text-center">
                        @if($user->is_active)
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-100 px-2.5 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                            Active
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                            Inactive
                        </span>
                        @endif
                    </td>

                    {{-- Last login --}}
                    <td class="px-5 py-4">
                        @if($user->last_login_at)
                        <div class="text-xs text-gray-700">{{ $user->last_login_at->format('d M Y') }}</div>
                        <div class="text-xs text-gray-400">{{ $user->last_login_at->format('h:i A') }}</div>
                        @else
                        <span class="text-xs text-gray-300">Never</span>
                        @endif
                    </td>

                    {{-- Login count --}}
                    <td class="px-5 py-4 text-center">
                        <span class="text-xs text-gray-600">{{ number_format($user->login_count ?? 0) }}</span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-4 text-right">
                        @if($user->id !== auth()->id())
                        <button onclick="openEditUser({{ $user->id }}, '{{ $user->role }}', {{ $user->is_active ? 'true' : 'false' }}, '{{ addslashes($user->name) }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-indigo-600 border border-gray-200 hover:border-indigo-300 rounded-lg hover:bg-indigo-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                            </svg>
                            Edit
                        </button>
                        @else
                        <span class="text-xs text-gray-300">—</span>
                        @endif
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <p class="text-gray-400">No users found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $users->links() }}
    </div>
    @endif
</div>

{{-- ─── INVITE USER MODAL ───────────────────────────────────── --}}
<div id="invite-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('invite-modal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Invite New User</h3>
            <button onclick="document.getElementById('invite-modal').classList.add('hidden')"
                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" required placeholder="Rahul Kumar"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" required placeholder="rahul@example.com"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Role <span class="text-red-500">*</span></label>
                <select name="role" required
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="viewer">Viewer — can view rankings only</option>
                    <option value="manager">Manager — can add/edit keywords</option>
                    <option value="admin">Admin — full access</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Password
                    <span class="font-normal text-gray-400 text-xs">(leave blank to auto-generate)</span>
                </label>
                <input type="password" name="password" placeholder="Min. 8 characters"
                       class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div class="bg-blue-50 rounded-lg px-4 py-3 text-xs text-blue-700">
                <svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                The temporary password will be shown after creation. Share it securely with the user.
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('invite-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Send Invite
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ─── EDIT USER MODAL ─────────────────────────────────────── --}}
<div id="edit-user-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('edit-user-modal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 class="font-semibold text-gray-900">Edit User</h3>
                <p id="edit-user-name" class="text-sm text-gray-500"></p>
            </div>
            <button onclick="document.getElementById('edit-user-modal').classList.add('hidden')"
                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="edit-user-form" method="POST" action="" class="p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Role</label>
                <select id="edit-user-role" name="role"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="viewer">Viewer</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Status</label>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" id="status-active" name="is_active" value="1"
                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" id="status-inactive" name="is_active" value="0"
                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Inactive</span>
                    </label>
                </div>
                <p class="text-xs text-gray-400 mt-2">Inactive users cannot log in.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('edit-user-modal').classList.add('hidden')"
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

@endsection

@push('scripts')
<script>
function openEditUser(id, role, isActive, name) {
    document.getElementById('edit-user-form').action = '/users/' + id;
    document.getElementById('edit-user-name').textContent = name;

    const roleSelect = document.getElementById('edit-user-role');
    for (let opt of roleSelect.options) opt.selected = opt.value === role;

    document.getElementById('status-active').checked = isActive;
    document.getElementById('status-inactive').checked = !isActive;

    document.getElementById('edit-user-modal').classList.remove('hidden');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('invite-modal')?.classList.add('hidden');
        document.getElementById('edit-user-modal')?.classList.add('hidden');
    }
});
</script>
@endpush
