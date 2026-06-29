<?php

namespace App\Services\Serp;

interface SerpProviderInterface
{
    /**
     * Get Google rank position for the given keyword.
     *
     * @param  string  $keyword    e.g. "mumbai darshan bus places"
     * @param  string  $targetUrl  e.g. "mumbaidarshanbusplaces.com"
     * @param  string  $country    ISO code e.g. "in"
     * @return int|null  Position 1-100, or null if not found
     */
    public function getRank(string $keyword, string $targetUrl, string $country = 'in'): ?int;

    /** Human-readable provider name shown in UI and logs */
    public function getName(): string;

    /** Test API key validity. Returns true/false. */
    public function testConnection(): bool;

    /** Remaining credits/quota. Return null if not available. */
    public function getRemainingCredits(): ?int;
}
