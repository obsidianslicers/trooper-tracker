<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Models\Event;
use App\Plugins\PluginManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use TroopTracker\Plugins\Xenforo\Models\EventThread;

trait UsesXenforoPlugin
{
    protected function enableXenforo(): PluginManager
    {
        $plugins = $this->app->make(PluginManager::class);
        $plugins->enable('xenforo');
        $plugins->load($plugins->findOrFail('xenforo'));

        // Rebuild Artisan so the plugin's commands are registered, as in a fresh process.
        $this->app->make(Kernel::class)->setArtisan(null);
        Route::getRoutes()->refreshNameLookups();

        return $plugins;
    }

    protected function linkThread(
        Event $event,
        ?int $thread_id,
        ?int $post_id = null,
        bool $should_create_thread = true,
    ): Event {
        EventThread::store($event->id, [
            EventThread::THREAD_ID => $thread_id,
            EventThread::POST_ID => $post_id,
            EventThread::SHOULD_CREATE_THREAD => $should_create_thread,
        ]);

        return $event;
    }
}
