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
        $log          = Log::channel('jobs');
        $targetDomain = $this->extractDomain($targetUrl);

        // Serper caps organic results at ~10 per call regardless of `num`, so
        // paginate via `page` to cover positions up to 100, stopping as soon
        // as a match is found or a short page shows there's nothing further.
        $perPage  = 10;
        $maxPages = 10;

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
                    'hl'   => 'en',
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

                foreach ($results as $result) {
                    if ($this->matches($this->extractDomain($result['link'] ?? ''), $targetDomain)) {
                        $log->debug('Serper API → match found', [
                            'keyword'     => $keyword,
                            'page'        => $page,
                            'rank'        => $result['position'],
                            'matched_url' => $result['link'] ?? '',
                            'title'       => $result['title'] ?? '',
                        ]);
                        return $result['position'];
                    }
                }

                // Short page (fewer results than requested) means Google has
                // nothing further to show — no point paginating any deeper.
                if (count($results) < $perPage) {
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
