<?php

namespace App\Services\Serp;

class SerpProviderFactory
{
    public static function make(): SerpProviderInterface
    {
        $provider = \App\Models\Setting::get('serp_provider')
            ?? config('serp.provider', 'serper');

        $apiKey = \App\Models\Setting::get('serp_api_key')
            ?? config('serp.api_key', '');

        return match($provider) {
            'serper'     => new SerperProvider($apiKey),
            'serpapi'    => new SerpApiProvider($apiKey),
            'dataforseo' => new DataForSeoProvider(
                \App\Models\Setting::get('serp_login') ?? config('serp.dataforseo_login', ''),
                $apiKey
            ),
            default => throw new \InvalidArgumentException(
                "Unknown SERP provider: [{$provider}]. Supported: serper, serpapi, dataforseo"
            ),
        };
    }
}
