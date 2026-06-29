<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\Serp\SerpProviderFactory;
use Illuminate\Http\Request;

class ApiSettingsController extends Controller
{
    public function index()
    {
        $providers      = config('serp.providers');
        $activeProvider = Setting::get('serp_provider') ?? config('serp.provider', 'serper');
        $country        = Setting::get('serp_country')  ?? config('serp.country', 'in');
        $rawKey         = Setting::get('serp_api_key')  ?? '';
        $maskedKey      = $rawKey
            ? str_repeat('*', max(0, strlen($rawKey) - 4)) . substr($rawKey, -4)
            : '';

        $credits = null;
        try {
            if ($rawKey) {
                $credits = SerpProviderFactory::make()->getRemainingCredits();
            }
        } catch (\Exception) {}

        return view('settings.api', compact(
            'providers', 'activeProvider', 'country', 'maskedKey', 'credits'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'provider' => ['required', 'in:serper,serpapi,dataforseo'],
            'api_key'  => ['required', 'string', 'min:8'],
            'country'  => ['required', 'string', 'size:2'],
        ]);

        $oldProvider = Setting::get('serp_provider');

        Setting::set('serp_provider', $request->provider);
        Setting::set('serp_country',  $request->country);

        // Only overwrite the key if user didn't submit the masked placeholder
        if (! str_contains($request->api_key, '***')) {
            Setting::set('serp_api_key', $request->api_key);
        }

        if ($request->provider === 'dataforseo' && $request->filled('serp_login')) {
            Setting::set('serp_login', $request->serp_login);
        }

        ActivityLog::record(
            userId:      auth()->id(),
            action:      'api.settings_updated',
            modelType:   'Setting',
            description: 'API provider settings updated',
            oldValues:   ['provider' => $oldProvider],
            newValues:   ['provider' => $request->provider, 'country' => $request->country]
        );

        return back()->with('success', 'API settings saved successfully.');
    }

    public function test()
    {
        try {
            $provider = SerpProviderFactory::make();
            $ok       = $provider->testConnection();
            $credits  = $provider->getRemainingCredits();

            return response()->json([
                'success' => $ok,
                'provider'=> $provider->getName(),
                'credits' => $credits,
                'message' => $ok
                    ? "Connected to {$provider->getName()} successfully."
                    : "Connection failed — check your API key.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
}
