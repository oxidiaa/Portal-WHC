<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SATURNUS Automated Email Reminder Broadcast Scheduler
Schedule::command('saturnus:send-scheduled-reminders')
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping();

