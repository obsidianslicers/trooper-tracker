<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Tests;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Plugins\PluginManager;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

trait UsesHolonetPlugin
{
    protected const GEORGIA = 'https://georgia.test';

    protected const DIRECTORY = 'https://directory.test/trackers.json';

    protected function enableHolonet(): PluginManager
    {
        config([
            'app.url' => 'https://florida.test',
            'plugins.holonet.directory_url' => self::DIRECTORY,
        ]);

        $plugins = $this->app->make(PluginManager::class);
        $plugins->enable('holonet');
        $plugins->load($plugins->findOrFail('holonet'));

        $this->app->make(Kernel::class)->setArtisan(null);
        Route::getRoutes()->refreshNameLookups();

        return $plugins;
    }

    /**
     * Fakes the shared tracker list (Florida, which is this tracker, and Georgia)
     * plus any other responses.
     *
     * @param  array<string, mixed>  $responses
     */
    protected function fakeTrackers(array $responses = []): void
    {
        Http::fake($responses + [
            self::DIRECTORY => Http::response(['trackers' => [
                ['name' => '501st Florida Garrison', 'url' => 'https://florida.test'],
                ['name' => '501st Georgia Garrison', 'url' => self::GEORGIA.'/'],
                ['name' => 'Broken', 'url' => 'not a url'],
            ]]),
        ]);
    }

    protected function attendedTroop(
        Trooper $trooper,
        string $starts_at,
        string $ends_at,
        array $attributes = [],
    ): EventTrooper {
        $event = Event::factory()->asClosed()->create($attributes);
        $shift = EventShift::factory()->forEvent($event)->create([
            EventShift::SHIFT_STARTS_AT => Carbon::parse($starts_at),
            EventShift::SHIFT_ENDS_AT => Carbon::parse($ends_at),
        ]);

        return EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();
    }
}
