<?php

use App\Console\Commands\AggregateDailyStats;
use App\Console\Commands\PruneOldData;
use App\Console\Commands\PublishScheduledVlogs;
use App\Console\Commands\SendWeeklyDigest;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
|
| On the server, one cron entry drives all of this:
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Publish anything whose scheduled time has arrived.
Schedule::command(PublishScheduledVlogs::class)->everyFiveMinutes()->withoutOverlapping();

// Roll up yesterday's numbers and expire finished ad campaigns.
Schedule::command(AggregateDailyStats::class)->dailyAt('00:20')->withoutOverlapping();

// Keep the raw view log from growing without limit.
Schedule::command(PruneOldData::class)->weeklyOn(1, '03:00');

// Weekly best-of email to newsletter subscribers.
Schedule::command(SendWeeklyDigest::class)->weeklyOn(5, '09:00');

// Housekeeping that ships with the framework.
Schedule::command('queue:prune-failed --hours=336')->daily();
Schedule::command('model:prune')->daily();
