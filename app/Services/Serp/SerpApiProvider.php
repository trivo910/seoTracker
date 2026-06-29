<?php

namespace App\Services\Serp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerpApiProvider implements SerpProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://serpapi.com';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getRank(string $keyword, string $targetUrl, string $country = 'in'): ?int
    {
        try {
            $response = Http::get("{$this->baseUrl}/search", [
                'api_key' => $this->apiKey,
                'q'       => $keyword,
                'gl'      => $country,
                'hl'      => 'en',
                'num'     => 100,
                'engine'  => 'google',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $results      = $response->json('organic_results') ?? [];
            $targetDomain = $this->extractDomain($targetUrl);

            foreach ($results as $result) {
                if ($this->matches($this->extractDomain($result['link'] ?? ''), $targetDomain)) {
                    return $result['position'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SerpAPI exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function getName(): string
    {
        return 'SerpAPI';
    }

    public function testConnection(): bool
    {
        try {
            return Http::get("{$this->baseUrl}/search", ['api_key' => $this->apiKey, 'q' => 'test', 'num' => 1, 'engine' => 'google'])->successful();
        } catch (\Exception) {
            return false;
        }
    }

    public function getRemainingCredits(): ?int
    {
        try {
            return Http::get("{$this->baseUrl}/account", ['api_key' => $this->apiKey])->json('plan_searches_left');
        } catch (\Exception) {
            return null;
        }
    }

    private function extractDomain(string $url): string
    {
        return strtolower(ltrim(parse_url($url, PHP_URL_HOST) ?? $url, 'www.'));
    }

    private function matches(string $a, string $b): bool
    {
        return str_contains($a, $b) || str_contains($b, $a);
    }
}
