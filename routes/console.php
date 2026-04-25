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
