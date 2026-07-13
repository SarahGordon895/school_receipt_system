<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Full automated fee reminders: 14 days, 7 days, 3 days, due day, and overdue — once daily.
Schedule::command('fees:send-reminders')->dailyAt('06:00');
