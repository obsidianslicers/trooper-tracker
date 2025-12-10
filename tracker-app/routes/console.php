<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('tracker:calculate-trooper-achievements')
    ->daily()
    ->at('05:00');

Schedule::command('tracker:synchronize-organizations')
    ->weekly()
    ->at('01:00');
