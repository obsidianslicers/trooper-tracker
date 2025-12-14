<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('tracker:close-event-shifts')
    ->hourly();

Schedule::command('tracker:close-events')
    ->dailyAt('01:00');

Schedule::command('tracker:calculate-trooper-achievements')
    ->dailyAt('05:00');

Schedule::command('tracker:synchronize-organizations')
    ->weeklyOn(0, '03:00');
