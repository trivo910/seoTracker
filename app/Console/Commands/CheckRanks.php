<?php

namespace App\Console\Commands;

use App\Jobs\RankCheckJob;
use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Setting;
use App\Services\Serp\SerpProviderFactory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckRanks extends Command
{
    protected $signature   = 'ranks:check {--dry-run : Show what would run without calling API}';
    protected $description = 'Check Google rankings for all active keywords';

    public function handle(): int
    {
        $this->info('RankTracker — Manual rank check');
        $this->info('Provider : ' . (Setting::get('serp_provider') ?? 'serper'));
        $this->info('Country  : ' . (Setting::get('serp_country')  ?? 'in'));
        $this->newLine();

        $keywords = Keyword::where('is_active', true)->get();

        if ($keywords->isEmpty()) {
            $this->warn('No active keywords found. Add keywords first.');
            return self::FAILURE;
        }

        $this->info("Found {$keywords->count()} active keywords.");

        if ($this->option('dry-run')) {
            $this->table(['#', 'Keyword', 'Target URL'], $keywords->map(fn($k, $i) => [
                $i + 1, $k->keyword, $k->target_url,
            ])->toArray());
            $this->info('Dry run — no API calls made.');
            return self::SUCCESS;
        }

        // Dispatch to queue (or run synchronously if QUEUE_CONNECTION=sync)
        RankCheckJob::dispatch();

        $this->info('RankCheckJob dispatched successfully.');
        $this->info('Check logs/laravel.log for progress.');

        return self::SUCCESS;
    }
}
