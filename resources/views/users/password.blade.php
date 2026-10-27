@extends('layouts.app')
@section('title', 'Change Password - SEO Rank Tracker')
@section('page-title', 'Change Password')
@section('content')
<div class="max-w-lg bg-white rounded-xl border border-gray-200 p-6">
    <h2 class="font-semibold text-gray-800">Change password for {{ $user->name }}</h2>
    <p class="text-sm text-gray-500 mt-1 mb-5">{{ $user->email }}</p>
    @if($errors->any())
    <div role="alert" class="bg-red-50 text-red-700 text-sm p-3 rounded-lg mb-4">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif
    <form method="POST" action="{{ route('users.password.update', $user) }}" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label for="current-password" class="block text-sm font-medium mb-1">Your current admin password</label>
            <input id="current-password" type="password" name="current_password" autocomplete="current-password" required class="w-full border rounded-lg p-2.5">
        </div>
        <div>
            <label for="new-password" class="block text-sm font-medium mb-1">New password</label>
            <input id="new-password" type="password" name="password" autocomplete="new-password" required minlength="8" class="w-full border rounded-lg p-2.5">
            <p class="text-xs text-gray-500 mt-1">At least 8 characters.</p>
        </div>
        <div>
            <label for="confirm-password" class="block text-sm font-medium mb-1">Confirm new password</label>
            <input id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" required minlength="8" class="w-full border rounded-lg p-2.5">
        </div>
        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Change Password</button>
            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
        </div>
    </form>
</div>
@endsection
