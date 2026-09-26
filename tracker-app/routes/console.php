<?php

use App\Console\Commands\CalculateTrooperAchievementsCommand;
use App\Console\Commands\CheckSupervisorHealthCommand;
use App\Console\Commands\CloseEventsCommand;
use App\Console\Commands\CloseEventShiftsCommand;
use App\Console\Commands\DispatchQueueHeartbeatCommand;
use App\Console\Commands\ExpireVisitorAccessCommand;
use App\Console\Commands\SendDailyEventNotifications;
use App\Console\Commands\SendDailyMilestoneNotifications;
use App\Console\Commands\SynchronizeOrganizations;
use App\Console\Commands\SynchronizeXenforoUsers;
use App\Console\Commands\RemindClosedEventShiftsCommand;
use App\Console\Commands\SendTentativeRemindersCommand;
use App\Console\Commands\ProcessAccountDeletionsCommand;
use Illuminate\Support\Facades\Schedule;

$timezone = config('tracker.calendar.timezone');

Schedule::command('auth:clear-resets')->everyFifteenMinutes();

Schedule::command(DispatchQueueHeartbeatCommand::class)->everyMinute();

Schedule::command(CheckSupervisorHealthCommand::class)->everyFiveMinutes();

Schedule::command(SynchronizeXenforoUsers::class)->hourly();

Schedule::command(SynchronizeOrganizations::class)
    ->weeklyOn(0, '03:00')
    ->timezone($timezone);

Schedule::command(CloseEventShiftsCommand::class)
    ->hourly()
    ->timezone($timezone);

Schedule::command(CloseEventsCommand::class)
    ->dailyAt('01:00')
    ->timezone($timezone);

Schedule::command(CalculateTrooperAchievementsCommand::class)
    ->dailyAt('02:00')
    ->timezone($timezone);

Schedule::command(ExpireVisitorAccessCommand::class)
    ->dailyAt('00:30')
    ->timezone($timezone);

Schedule::command(SendDailyEventNotifications::class)
    ->dailyAt('08:00')
    ->timezone($timezone);

Schedule::command(SendDailyMilestoneNotifications::class)
    ->dailyAt('08:00')
    ->timezone($timezone)
    ->withoutOverlapping();

Schedule::command(RemindClosedEventShiftsCommand::class)
    ->dailyAt('09:00')
    ->timezone($timezone);

Schedule::command(SendTentativeRemindersCommand::class)
    ->dailyAt('08:00')
    ->timezone($timezone);

Schedule::command(ProcessAccountDeletionsCommand::class)
    ->dailyAt('03:30')
    ->timezone($timezone);
