<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Setting;
use App\Services\Serp\SerpProviderFactory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RankCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(public ?int $keywordId = null, public ?int $websiteId = null)
    {
    }

    public function handle(): void
    {
        $log       = Log::channel('jobs');
        $startTime = microtime(true);

        $provider = SerpProviderFactory::make();
        $country  = Setting::get('serp_country') ?? config('serp.country', 'in');
        $language = Setting::get('serp_language') ?? config('serp.language', 'en');
        $today    = Carbon::today()->toDateString();

        $keywordQuery = Keyword::with('website');
        if ($this->keywordId === null) {
            $keywordQuery->where('is_active', true);
        } else {
            $keywordQuery->whereKey($this->keywordId);
        }

        if ($this->websiteId !== null) {
            $keywordQuery->where('website_id', $this->websiteId);
        }

        $keywords = $keywordQuery->get();

        $log->info('RankCheckJob started', [
            'provider'       => $provider->getName(),
            'country'        => $country,
            'language'       => $language,
            'date'           => $today,
            'total_keywords' => $keywords->count(),
        ]);

        $checked = 0;
        $failed  = 0;
        $skipped = 0;

        foreach ($keywords as $keyword) {

            // Skip if already checked today
            $alreadyChecked = KeywordRanking::where('keyword_id', $keyword->id)
                ->whereDate('checked_date', $today)
                ->exists();

            if ($alreadyChecked && $this->keywordId === null) {
                $skipped++;
                $log->debug('Skipped — already checked today', [
                    'keyword' => $keyword->keyword,
                    'website' => $keyword->website?->name,
                ]);
                continue;
            }

            $log->info('Checking keyword', [
                'keyword'    => $keyword->keyword,
                'target_url' => $keyword->target_url,
                'website'    => $keyword->website?->name,
                'country'    => $country,
            ]);

            try {
                $rank = $provider->getRank(
                    $keyword->keyword,
                    $keyword->target_url,
                    $country
                );

                $ranking = KeywordRanking::where('keyword_id', $keyword->id)
                    ->whereDate('checked_date', $today)
                    ->first() ?? new KeywordRanking([
                        'keyword_id' => $keyword->id,
                        'checked_date' => $today,
                    ]);

                $ranking->fill([
                    'rank_position' => $rank,
                    'provider'      => $provider->getName(),
                    'checked_at'    => now(),
                ])->save();

                $checked++;

                if ($rank !== null) {
                    $log->info('Rank recorded', [
                        'keyword' => $keyword->keyword,
                        'rank'    => $rank,
                        'website' => $keyword->website?->name,
                    ]);
                } else {
                    $log->warning('Not ranked in top 100', [
                        'keyword' => $keyword->keyword,
                        'website' => $keyword->website?->name,
                    ]);
                }

                // Avoid API rate limiting
                sleep(1);

            } catch (\Exception $e) {
                $failed++;
                $log->error('Keyword check failed', [
                    'keyword' => $keyword->keyword,
                    'website' => $keyword->website?->name,
                    'error'   => $e->getMessage(),
                    'class'   => get_class($e),
                ]);
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $log->info('RankCheckJob completed', [
            'checked'      => $checked,
            'failed'       => $failed,
            'skipped'      => $skipped,
            'duration_sec' => $duration,
        ]);
    }
}
