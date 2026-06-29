<?php

/**
 * config/serp.php
 *
 * All SERP API configuration in one place.
 * Values come from .env — never hardcode keys here.
 *
 * TO SWAP PROVIDER:
 *   1. Change SERP_PROVIDER in .env  (serper | serpapi | dataforseo)
 *   2. Or change it from Admin → API Settings (stored in DB, takes priority)
 */
return [

    'provider'          => env('SERP_PROVIDER', 'serper'),
    'api_key'           => env('SERP_API_KEY', ''),
    'dataforseo_login'  => env('DATAFORSEO_LOGIN', ''),
    'country'           => env('SERP_COUNTRY', 'in'),
    'language'          => env('SERP_LANGUAGE', 'en'),
    'results'           => env('SERP_RESULTS', 100),

    // Schedule — runs daily at 1 AM by default
    'schedule_time'     => env('SERP_SCHEDULE_TIME', '01:00'),

    /*
    |--------------------------------------------------------------------------
    | Available providers — shown in Admin → API Settings UI
    | Add a new provider here after creating its Provider class
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'serper' => [
            'name'        => 'Serper.dev',
            'url'         => 'https://serper.dev',
            'pricing'     => '2,500 free credits · $50 for 50,000',
            'recommended' => true,
            'fields'      => ['api_key'],
        ],
        'serpapi' => [
            'name'        => 'SerpAPI',
            'url'         => 'https://serpapi.com',
            'pricing'     => '$75/mo for 5,000 searches',
            'recommended' => false,
            'fields'      => ['api_key'],
        ],
        'dataforseo' => [
            'name'        => 'DataForSEO',
            'url'         => 'https://dataforseo.com',
            'pricing'     => '$0.0015 per search (pay-as-you-go)',
            'recommended' => false,
            'fields'      => ['login', 'api_key'],
        ],
    ],
];
