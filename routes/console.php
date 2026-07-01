<?php

use App\Jobs\RankCheckJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler — runs RankCheckJob every day at 1 AM
|--------------------------------------------------------------------------
|
| On your server, add ONE cron entry:
|   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
|
| Laravel's scheduler handles the rest automatically.
|
| You can change the time in .env:
|   SERP_SCHEDULE_TIME=01:00
|
| Or run manually anytime:
|   php artisan ranks:check
|
*/

Schedule::job(new RankCheckJob())
    // ->dailyAt(config('serp.schedule_time', '01:00'))
    ->weeklyOn(3, config('serp.schedule_time', '01:00'))
    ->withoutOverlapping()
    ->onOneServer()
    ->name('daily-rank-check')
    ->description('Check Google rankings for all active keywords');
