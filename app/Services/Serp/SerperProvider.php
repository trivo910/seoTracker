<?php

namespace App\Services\Serp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerperProvider implements SerpProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://google.serper.dev';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getRank(string $keyword, string $targetUrl, string $country = 'in'): ?int
    {
        $log = Log::channel('jobs');

        try {
            $log->debug('Serper API → request', [
                'keyword' => $keyword,
                'country' => $country,
                'num'     => 100,
                'url'     => "{$this->baseUrl}/search",
            ]);

            $response = Http::withHeaders([
                'X-API-KEY'    => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/search", [
                'q'   => $keyword,
                'gl'  => $country,
                'hl'  => 'en',
                'num' => 100,
            ]);

            if (! $response->successful()) {
                $log->error('Serper API → error response', [
                    'keyword' => $keyword,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);
                return null;
            }

            $results      = $response->json('organic') ?? [];
            $targetDomain = $this->extractDomain($targetUrl);
            $credits      = $response->header('X-RateLimit-Remaining');

            $log->debug('Serper API → response received', [
                'keyword'        => $keyword,
                'http_status'    => $response->status(),
                'organic_count'  => count($results),
                'target_domain'  => $targetDomain,
                'credits_left'   => $credits,
            ]);

            foreach ($results as $result) {
                if ($this->matches($this->extractDomain($result['link'] ?? ''), $targetDomain)) {
                    $log->debug('Serper API → match found', [
                        'keyword'     => $keyword,
                        'rank'        => $result['position'],
                        'matched_url' => $result['link'] ?? '',
                        'title'       => $result['title'] ?? '',
                    ]);
                    return $result['position'];
                }
            }

            $log->debug('Serper API → no match in results', [
                'keyword'       => $keyword,
                'target_domain' => $targetDomain,
                'results_shown' => count($results),
            ]);

            return null;

        } catch (\Exception $e) {
            $log->error('Serper API → exception', [
                'keyword' => $keyword,
                'error'   => $e->getMessage(),
                'class'   => get_class($e),
            ]);
            return null;
        }
    }

    public function getName(): string { return 'Serper.dev'; }

    public function testConnection(): bool
    {
        try {
            return Http::withHeaders(['X-API-KEY' => $this->apiKey, 'Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/search", ['q' => 'test', 'num' => 1])
                ->successful();
        } catch (\Exception) { return false; }
    }

    public function getRemainingCredits(): ?int { return null; }

    private function extractDomain(string $url): string
    {
        return strtolower(ltrim(parse_url($url, PHP_URL_HOST) ?? $url, 'www.'));
    }

    private function matches(string $a, string $b): bool
    {
        return str_contains($a, $b) || str_contains($b, $a);
    }
}
