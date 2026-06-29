@extends('layouts.app')
@section('title', 'Profile — SEO Rank Tracker')
@section('page-title', 'My Profile')

@section('content')

<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 p-8">

        {{-- Avatar --}}
        @php
            $avatarBg = match(auth()->user()->avatarColor) {
                'blue'   => 'bg-blue-500',
                'green'  => 'bg-emerald-500',
                'amber'  => 'bg-amber-500',
                'purple' => 'bg-violet-500',
                default  => 'bg-indigo-500',
            };
        @endphp

        <div class="flex items-center gap-5 mb-8">
            <div class="w-16 h-16 rounded-full {{ $avatarBg }} flex items-center justify-center text-white text-2xl font-bold">
                {{ auth()->user()->initials }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ auth()->user()->name }}</h2>
                <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">Role</span>
                <span class="text-sm font-semibold text-gray-800">{{ ucfirst(auth()->user()->role) }}</span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">Account status</span>
                <span class="text-sm font-semibold {{ auth()->user()->is_active ? 'text-green-600' : 'text-red-600' }}">
                    {{ auth()->user()->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="flex items-center justify-between py-3 border-b border-gray-100">
                <span class="text-sm font-medium text-gray-600">Last login</span>
                <span class="text-sm text-gray-700">
                    {{ auth()->user()->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}
                </span>
            </div>
            <div class="flex items-center justify-between py-3">
                <span class="text-sm font-medium text-gray-600">Total logins</span>
                <span class="text-sm text-gray-700">{{ number_format(auth()->user()->login_count ?? 0) }}</span>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100">
            <p class="text-xs text-gray-400 text-center">
                To change your password or email, contact an administrator.
            </p>
        </div>
    </div>
</div>

@endsection
