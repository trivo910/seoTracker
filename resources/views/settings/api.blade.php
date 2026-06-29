@extends('layouts.app')
@section('title', 'API Settings — SEO Rank Tracker')
@section('page-title', 'API Settings')

@section('content')

<div class="max-w-3xl space-y-6">

    {{-- ─── Provider selection ──────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">SERP API Provider</h2>
            <p class="text-sm text-gray-500 mt-0.5">Choose which search API to use for ranking checks.</p>
        </div>

        <form method="POST" action="{{ route('settings.api.update') }}" class="p-6" id="api-form">
            @csrf @method('PUT')

            {{-- Provider cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                @foreach($providers as $key => $provider)
                <label class="relative cursor-pointer">
                    <input type="radio" name="provider" value="{{ $key }}"
                           {{ $activeProvider === $key ? 'checked' : '' }}
                           class="sr-only peer"
                           onchange="toggleDataForSeoFields()">
                    <div class="border-2 rounded-xl p-4 transition-all
                        peer-checked:border-indigo-500 peer-checked:bg-indigo-50
                        border-gray-200 hover:border-gray-300">

                        <div class="flex items-start justify-between mb-2">
                            <p class="font-semibold text-sm text-gray-800">{{ $provider['name'] }}</p>
                            @if($provider['recommended'] ?? false)
                            <span class="text-xs bg-green-100 text-green-700 font-medium px-1.5 py-0.5 rounded">Best</span>
                            @endif
                        </div>

                        <p class="text-xs text-gray-500">{{ $provider['pricing'] }}</p>

                        <div class="mt-3 flex items-center gap-1.5">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center
                                {{ $activeProvider === $key ? 'border-indigo-500' : 'border-gray-300' }}">
                                @if($activeProvider === $key)
                                <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                @endif
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ $activeProvider === $key ? 'Selected' : 'Select' }}
                            </span>
                        </div>
                    </div>
                </label>
                @endforeach
            </div>

            {{-- API Key --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        API Key <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" name="api_key"
                               value="{{ $maskedKey }}"
                               id="api-key-input"
                               required
                               placeholder="Enter your API key"
                               class="w-full px-3.5 py-2.5 pr-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono">
                        <button type="button" onclick="toggleKeyVisibility()"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                            <svg id="eye-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Get your key at
                        <a href="{{ $providers[$activeProvider]['url'] ?? '#' }}" target="_blank"
                           class="text-indigo-500 hover:underline" id="provider-url">
                            {{ $providers[$activeProvider]['url'] ?? '' }}
                        </a>
                    </p>
                </div>

                {{-- DataForSEO extra field --}}
                <div id="dataforseo-fields" class="{{ $activeProvider !== 'dataforseo' ? 'hidden' : '' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">DataForSEO Login Email</label>
                    <input type="text" name="serp_login"
                           placeholder="your@email.com"
                           class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                {{-- Country --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Search Country</label>
                    <select name="country"
                            class="w-full max-w-xs px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @php
                        $countries = [
                            'in' => 'India (IN)',
                            'us' => 'United States (US)',
                            'gb' => 'United Kingdom (GB)',
                            'au' => 'Australia (AU)',
                            'ca' => 'Canada (CA)',
                            'sg' => 'Singapore (SG)',
                            'ae' => 'UAE (AE)',
                        ];
                        @endphp
                        @foreach($countries as $code => $label)
                        <option value="{{ $code }}" {{ $country === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Save --}}
            <div class="flex items-center gap-3 mt-6 pt-6 border-t border-gray-100">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Save Settings
                </button>

                <button type="button" onclick="testConnection()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg id="test-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Test Connection
                </button>

                <div id="test-result" class="text-sm hidden"></div>
            </div>
        </form>
    </div>

    {{-- ─── Current status card ─────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Current Status</h3>

        <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-1">Active Provider</p>
                <p class="font-semibold text-gray-800">{{ $providers[$activeProvider]['name'] ?? ucfirst($activeProvider) }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-1">Remaining Credits</p>
                @if($credits !== null)
                <p class="font-semibold text-gray-800">{{ number_format($credits) }}</p>
                @else
                <p class="font-semibold text-gray-400">—</p>
                @endif
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-1">Search Country</p>
                <p class="font-semibold text-gray-800 uppercase">{{ $country }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500 mb-1">API Key</p>
                <p class="font-semibold text-gray-800 font-mono text-sm">
                    {{ $maskedKey ?: '— not set —' }}
                </p>
            </div>
        </div>

        @if($credits !== null && $credits < 500)
        <div class="mt-4 flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 rounded-lg">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            Low credits! Only {{ number_format($credits) }} credits remaining. Recharge your account.
        </div>
        @endif
    </div>

    {{-- ─── Schedule info ───────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-2">Automatic Rank Checks</h3>
        <p class="text-sm text-gray-600">
            Rankings are automatically fetched every day at
            <span class="font-semibold text-gray-800">{{ config('serp.schedule_time', '01:00') }}</span>
            (server time).
        </p>
        <p class="text-sm text-gray-500 mt-1">
            You can also trigger a manual refresh from the Dashboard using the <span class="font-medium">Refresh</span> button.
        </p>
    </div>

</div>

@endsection

@push('scripts')
<script>
function toggleKeyVisibility() {
    const input = document.getElementById('api-key-input');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function toggleDataForSeoFields() {
    const provider = document.querySelector('input[name="provider"]:checked')?.value;
    const fields = document.getElementById('dataforseo-fields');
    fields.classList.toggle('hidden', provider !== 'dataforseo');
}

async function testConnection() {
    const spinner = document.getElementById('test-spinner');
    const result = document.getElementById('test-result');

    spinner.classList.remove('hidden');
    result.classList.add('hidden');

    try {
        const resp = await fetch('{{ route("settings.api.test") }}', {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await resp.json();

        result.classList.remove('hidden');
        if (data.success) {
            result.className = 'text-sm text-green-700 bg-green-50 px-3 py-2 rounded-lg';
            result.textContent = '✓ ' + data.message + (data.credits ? ` (${Number(data.credits).toLocaleString()} credits)` : '');
        } else {
            result.className = 'text-sm text-red-700 bg-red-50 px-3 py-2 rounded-lg';
            result.textContent = '✗ ' + data.message;
        }
    } catch(e) {
        result.classList.remove('hidden');
        result.className = 'text-sm text-red-700 bg-red-50 px-3 py-2 rounded-lg';
        result.textContent = 'Connection error — check your API key.';
    } finally {
        spinner.classList.add('hidden');
    }
}
</script>
@endpush
