<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('exceptions:send-digests')->everyMinute();
Schedule::command('projects:check-uptime')->everyMinute();
Schedule::command('exceptions:prune-old')->dailyAt('04:00');

// Updates: the one an administrator asked for starts within a minute; the nightly one
// runs while automatic updates are on (app:update, Administration → Updates).
Schedule::command('app:update pending')->everyMinute()->runInBackground();
Schedule::command('app:update apply --auto')->dailyAt(config('updates.time'))->runInBackground();
