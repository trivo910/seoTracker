<?php

namespace App\Services\Serp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataForSeoProvider implements SerpProviderInterface
{
    private string $login;
    private string $password;
    private string $baseUrl = 'https://api.dataforseo.com/v3';

    public function __construct(string $login, string $password)
    {
        $this->login    = $login;
        $this->password = $password;
    }

    public function getRank(string $keyword, string $targetUrl, string $country = 'in'): ?int
    {
        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->post("{$this->baseUrl}/serp/google/organic/live/advanced", [[
                    'keyword'       => $keyword,
                    'location_code' => $this->countryCode($country),
                    'language_code' => 'en',
                    'depth'         => 100,
                ]]);

            if (! $response->successful()) {
                return null;
            }

            $items        = $response->json('tasks.0.result.0.items') ?? [];
            $targetDomain = $this->extractDomain($targetUrl);

            foreach ($items as $item) {
                if (($item['type'] ?? '') !== 'organic') {
                    continue;
                }

                if ($this->matches($this->extractDomain($item['url'] ?? ''), $targetDomain)) {
                    return $item['rank_absolute'];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('DataForSEO exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function getName(): string
    {
        return 'DataForSEO';
    }

    public function testConnection(): bool
    {
        try {
            return Http::withBasicAuth($this->login, $this->password)
                ->get("{$this->baseUrl}/appendix/user_data")->successful();
        } catch (\Exception) {
            return false;
        }
    }

    public function getRemainingCredits(): ?int
    {
        return null;
    }

    private function countryCode(string $c): int
    {
        return match(strtolower($c)) {
            'in' => 2356,
            'us' => 2840,
            'uk' => 2826,
            default => 2356,
        };
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
