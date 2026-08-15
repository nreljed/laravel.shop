<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('schedule:publish')->everyMinute();
Schedule::command('shop:abandoned-cart-process')->hourly();
Schedule::command('webhooks:process')->everyMinute();
Schedule::command('shop:release-expired-holds')->everyFiveMinutes();
Schedule::command('shop:process-scheduled-sales')->everyMinute();

// Server-side self-deploy: if CI cannot reach the host (hosting firewall
// blocks datacenter IPs), the cron picks up new GitHub releases by itself.
Schedule::command('deploy:auto')
    ->everyFiveMinutes()
    ->withoutOverlapping();
