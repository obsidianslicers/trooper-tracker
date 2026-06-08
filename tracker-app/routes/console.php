<?php

use App\Jobs\UpdateEventForumThreadJob;
use Illuminate\Support\Facades\Schedule;

$timezone = config('tracker.calendar.timezone');

Schedule::command('auth:clear-resets')->everyFifteenMinutes();

Schedule::command('tracker:synchronize-xenforo-users')->hourly();

Schedule::command('tracker:synchronize-organizations')
    ->weeklyOn(0, '03:00')
    ->timezone($timezone);

Schedule::command('tracker:close-event-shifts')
    ->hourly()
    ->timezone($timezone);

Schedule::command('tracker:close-events')
    ->dailyAt('01:00')
    ->timezone($timezone);

Schedule::command('tracker:calculate-trooper-achievements')
    ->dailyAt('02:00')
    ->timezone($timezone);

Schedule::command('tracker:expire-visitor-access')
    ->dailyAt('00:30')
    ->timezone($timezone);

Schedule::command('tracker:send-daily-event-notifications')
    ->dailyAt('08:00')
    ->timezone($timezone);

Schedule::command('tracker:remind-closed-event-shifts')
    ->dailyAt('09:00')
    ->timezone($timezone);

Schedule::command('tracker:send-tentative-reminders')
    ->dailyAt('08:00')
    ->timezone($timezone);

Schedule::command('tracker:process-account-deletions')
    ->dailyAt('03:30')
    ->timezone($timezone);