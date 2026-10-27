<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class ApiSettingsController extends Controller
{
    private function settings(): array
    {
        $values = [];
        foreach (['provider', 'api_key', 'country', 'language', 'results'] as $field) {
            $values[$field] = Setting::get('serp_'.$field) ?? config('serp.'.$field);
        }
        $values['serp_login'] = Setting::get('serp_login') ?? config('serp.dataforseo_login', '');
        return $values;
    }

    public function index()
    {
        $settings = $this->settings();
        $now = now(config('app.timezone', 'UTC'));
        $nextRun = $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(4)
            ->setTimeFromTimeString(config('serp.schedule_time', '12:50'));
        if ($nextRun->lessThanOrEqualTo($now)) {
            $nextRun->addWeek();
        }

        return view('settings.api', [
            'providers' => config('serp.providers'),
            'activeProvider' => $settings['provider'],
            'country' => $settings['country'],
            'language' => $settings['language'],
            'results' => $settings['results'],
            'serpLogin' => $settings['serp_login'],
            'hasKey' => filled($settings['api_key']),
            'nextRun' => $nextRun,
        ]);
    }

    private function validatedSettings(Request $request): array
    {
        $current = $this->settings();
        $data = $request->validate([
            'provider' => ['required', Rule::in(array_keys(config('serp.providers')))],
            'api_key' => ['nullable', 'string', 'max:2048'],
            'country' => ['required', 'regex:/^[a-zA-Z]{2}$/'],
            'language' => ['required', 'regex:/^[a-zA-Z]{2,3}(-[a-zA-Z]{2,4})?$/'],
            'results' => ['required', 'integer', 'min:1', 'max:100'],
            'serp_login' => ['nullable', 'required_if:provider,dataforseo', 'email', 'max:255'],
        ]);
        if (empty($data['api_key'])) {
            if ($data['provider'] !== $current['provider'] || blank($current['api_key'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'api_key' => 'Enter an API key for the selected provider.',
                ]);
            }
            $data['api_key'] = $current['api_key'];
        }
        $data['country'] = strtolower($data['country']);
        return $data;
    }

    public function update(Request $request)
    {
        $data = $this->validatedSettings($request);
        DB::transaction(function () use ($data) {
            foreach (['provider', 'api_key', 'country', 'language', 'results'] as $field) {
                Setting::set('serp_'.$field, (string) $data[$field]);
            }
            if ($data['provider'] === 'dataforseo') {
                Setting::set('serp_login', $data['serp_login']);
            }
            ActivityLog::record(
                userId: auth()->id(), action: 'api.settings_updated', modelType: 'Setting',
                description: 'API provider settings updated',
                newValues: array_intersect_key($data, array_flip(['provider', 'country', 'language', 'results']))
            );
        });
        return back()->with('success', 'API settings saved successfully.');
    }

    public function test(Request $request)
    {
        $data = $this->validatedSettings($request);
        try {
            $http = Http::connectTimeout(10)->timeout(30);
            $query = ['q' => 'test', 'gl' => $data['country'], 'hl' => $data['language'], 'num' => (int) $data['results']];
            $response = match ($data['provider']) {
                'serper' => $http->withHeaders(['X-API-KEY' => $data['api_key']])
                    ->post('https://google.serper.dev/search', $query),
                'serpapi' => $http->get('https://serpapi.com/search', $query + ['api_key' => $data['api_key'], 'engine' => 'google']),
                'dataforseo' => $http->withBasicAuth($data['serp_login'], $data['api_key'])
                    ->get('https://api.dataforseo.com/v3/appendix/user_data'),
            };
            $body = $response->json();
            $success = $response->successful() && is_array($body)
                && empty($body['error'])
                && ($data['provider'] === 'dataforseo'
                    ? isset($body['status_code']) && (int) $body['status_code'] === 20000
                    : array_key_exists($data['provider'] === 'serper' ? 'organic' : 'organic_results', $body))
                && (!isset($body['status_code']) || (int) $body['status_code'] === 20000);
            $message = $body['error'] ?? $body['message'] ?? $body['status_message'] ?? null;
            if (!is_string($message)) {
                $message = $success ? 'API request completed successfully.' : 'The API returned an invalid or unsuccessful response.';
            }
            $message = str_replace($data['api_key'], '[redacted]', $message);
            return response()->json([
                'success' => $success,
                'message' => config('serp.providers.'.$data['provider'].'.name').' (HTTP '.$response->status().'): '.mb_substr($message, 0, 500),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return response()->json(['success' => false, 'message' => 'Unable to reach the API. The connection failed or timed out. Please try again.']);
        }
    }
}
