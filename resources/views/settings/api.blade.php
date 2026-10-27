@extends('layouts.app')
@section('title', 'API Settings - SEO Rank Tracker')
@section('page-title', 'API Settings')
@section('content')
<div class="max-w-3xl bg-white rounded-xl border border-gray-200 p-6">
    <h2 class="font-semibold text-gray-800">SERP API Settings</h2>
    <p class="text-sm text-gray-500 mt-1 mb-6">Only administrators can change or test these settings. Saved values override the .env defaults.</p>
    @if($errors->any())
    <div role="alert" class="text-sm text-red-700 bg-red-50 p-3 rounded-lg mb-4">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
    @endif
    <form method="POST" action="{{ route('settings.api.update') }}" id="api-form" class="space-y-5">
        @csrf @method('PUT')
        <div>
            <label for="provider" class="block text-sm font-medium mb-1">Provider (SERP_PROVIDER)</label>
            <select id="provider" name="provider" class="w-full border rounded-lg p-2.5" required>
                @foreach($providers as $key => $provider)
                <option value="{{ $key }}" @selected(old('provider', $activeProvider) === $key)>{{ $provider['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="api-key-input" class="block text-sm font-medium mb-1">API Key (SERP_API_KEY)</label>
            <input type="password" id="api-key-input" name="api_key" autocomplete="new-password" maxlength="2048"
                placeholder="{{ $hasKey ? 'Key configured; leave blank to keep it' : 'Enter your API key' }}"
                class="w-full border rounded-lg p-2.5">
            <p class="text-xs text-gray-500 mt-1">Enter a new key when changing providers. Your saved key is never displayed.</p>
        </div>
        <div id="dataforseo-fields">
            <label for="serp-login" class="block text-sm font-medium mb-1">DataForSEO Login Email</label>
            <input type="email" id="serp-login" name="serp_login" value="{{ old('serp_login', $serpLogin) }}" class="w-full border rounded-lg p-2.5">
        </div>
        <div>
            <label for="country" class="block text-sm font-medium mb-1">Search Country (SERP_COUNTRY)</label>
            <input id="country" name="country" value="{{ old('country', $country) }}" required pattern="[a-zA-Z]{2}" maxlength="2" class="w-full border rounded-lg p-2.5">
            <p class="text-xs text-gray-500 mt-1">Two-letter country code, for example in, us, or gb.</p>
        </div>
        <div>
            <label for="language" class="block text-sm font-medium mb-1">Search Language (SERP_LANGUAGE)</label>
            <input id="language" name="language" value="{{ old('language', $language) }}" required pattern="[a-zA-Z]{2,3}(-[a-zA-Z]{2,4})?" class="w-full border rounded-lg p-2.5">
            <p class="text-xs text-gray-500 mt-1">Language code, for example en or hi.</p>
        </div>
        <div>
            <label for="results" class="block text-sm font-medium mb-1">Results to Check (SERP_RESULTS)</label>
            <input type="number" id="results" name="results" value="{{ old('results', $results) }}" required min="1" max="100" class="w-full border rounded-lg p-2.5">
            <p class="text-xs text-gray-500 mt-1">Check up to 100 results per keyword.</p>
        </div>
        <div class="flex gap-3 pt-4 border-t">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg">Save Settings</button>
            <button type="button" id="test-button" class="px-5 py-2.5 border rounded-lg disabled:opacity-50">Test Connection</button>
        </div>
        <p class="text-xs text-gray-500">Testing uses the values above without saving them and may consume API credits. DataForSEO testing verifies account credentials.</p>
        <div id="test-result" role="status" aria-live="polite" class="hidden text-sm p-3 rounded-lg"></div>
    </form>
</div>
<p class="max-w-3xl text-sm text-gray-600 mt-4">
    Auto cron is scheduled to run next on <strong>{{ $nextRun->format('l, d F Y') }}</strong>
    at <strong>{{ $nextRun->format('h:i A') }}</strong> ({{ $nextRun->timezoneName }}).
</p>
@endsection
@push('scripts')
<script>
const apiForm = document.getElementById('api-form');
const providerInput = document.getElementById('provider');
const testButton = document.getElementById('test-button');
function toggleDataForSeoFields() {
    const active = providerInput.value === 'dataforseo';
    document.getElementById('dataforseo-fields').classList.toggle('hidden', !active);
    document.getElementById('serp-login').required = active;
}
providerInput.addEventListener('change', toggleDataForSeoFields);
toggleDataForSeoFields();
testButton.addEventListener('click', async () => {
    if (!apiForm.reportValidity()) return;
    const result = document.getElementById('test-result');
    testButton.disabled = true;
    testButton.textContent = 'Testing...';
    result.className = 'text-sm p-3 rounded-lg bg-gray-50';
    result.textContent = 'Contacting the API...';
    const body = new FormData(apiForm);
    body.delete('_method');
    try {
        const response = await fetch(@json(route('settings.api.test')), {
            method: 'POST', body,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': body.get('_token') }
        });
        let data;
        try { data = await response.json(); }
        catch { throw new Error('The server returned an unexpected response. Refresh the page and try again.'); }
        if (data.errors) data.message = Object.values(data.errors).flat().join(' ');
        result.className = 'text-sm p-3 rounded-lg ' + (data.success ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50');
        result.textContent = data.message || 'The API test failed. Please try again.';
    } catch (error) {
        result.className = 'text-sm p-3 rounded-lg text-red-700 bg-red-50';
        result.textContent = error.message || 'Unable to contact the server. Please try again.';
    } finally {
        testButton.disabled = false;
        testButton.textContent = 'Test Connection';
    }
});
</script>
@endpush
