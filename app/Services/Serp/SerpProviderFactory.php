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

        $language = \App\Models\Setting::get('serp_language') ?? config('serp.language', 'en');
        $results = (int) (\App\Models\Setting::get('serp_results') ?? config('serp.results', 100));

        return match($provider) {
            'serper'     => new SerperProvider($apiKey, $language, $results),
            'serpapi'    => new SerpApiProvider($apiKey, $language, $results),
            'dataforseo' => new DataForSeoProvider(
                \App\Models\Setting::get('serp_login') ?? config('serp.dataforseo_login', ''),
                $apiKey, $language, $results
            ),
            default => throw new \InvalidArgumentException(
                "Unknown SERP provider: [{$provider}]. Supported: serper, serpapi, dataforseo"
            ),
        };
    }
}
