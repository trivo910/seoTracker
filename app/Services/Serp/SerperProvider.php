<?php

namespace App\Services\Serp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SerperProvider implements SerpProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://google.serper.dev';

    public function __construct(string $apiKey, private string $language = 'en', private int $results = 100)
    {
        $this->apiKey = $apiKey;
    }

    public function getRank(string $keyword, string $targetUrl, string $country = 'in'): ?int
    {
        $log          = Log::channel('jobs');
        $targetDomain = $this->extractDomain($targetUrl);

        // Serper returns at most 10 organic results per page. Continue through
        // short pages because Google can return fewer than 10 results on a page.
        $perPage  = 10;
        $maxPages = (int) ceil($this->results / $perPage);

        try {
            for ($page = 1; $page <= $maxPages; $page++) {
                $log->debug('Serper API → request', [
                    'keyword' => $keyword,
                    'country' => $country,
                    'page'    => $page,
                    'url'     => "{$this->baseUrl}/search",
                ]);

                $response = Http::withHeaders([
                    'X-API-KEY'    => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}/search", [
                    'q'    => $keyword,
                    'gl'   => $country,
                    'hl'   => $this->language,
                    'num'  => $perPage,
                    'page' => $page,
                ]);

                if (! $response->successful()) {
                    $log->error('Serper API → error response', [
                        'keyword' => $keyword,
                        'page'    => $page,
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                    ]);
                    return null;
                }

                $results = $response->json('organic') ?? [];
                $credits = $response->header('X-RateLimit-Remaining');

                $log->debug('Serper API → response received', [
                    'keyword'       => $keyword,
                    'page'          => $page,
                    'http_status'   => $response->status(),
                    'organic_count' => count($results),
                    'target_domain' => $targetDomain,
                    'credits_left'  => $credits,
                ]);

                foreach ($results as $index => $result) {
                    if ((($page - 1) * $perPage) + $index >= $this->results) {
                        break;
                    }
                    if ($this->matches($this->extractDomain($result['link'] ?? ''), $targetDomain)) {
                        $rank = (($page - 1) * $perPage) + $index + 1;
                        $log->debug('Serper API → match found', [
                            'keyword'     => $keyword,
                            'page'        => $page,
                            'rank'        => $rank,
                            'matched_url' => $result['link'] ?? '',
                            'title'       => $result['title'] ?? '',
                        ]);
                        return $rank;
                    }
                }

                if ($results === []) {
                    break;
                }
            }

            $log->debug('Serper API → no match in results', [
                'keyword'       => $keyword,
                'target_domain' => $targetDomain,
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
        $domain = strtolower(parse_url($url, PHP_URL_HOST) ?? $url);

        return str_starts_with($domain, 'www.') ? substr($domain, 4) : $domain;
    }

    private function matches(string $a, string $b): bool
    {
        return $a === $b || str_ends_with($a, '.'.$b);
    }
}
