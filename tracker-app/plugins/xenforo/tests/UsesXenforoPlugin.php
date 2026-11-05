<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Route;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;

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

    /**
     * Create an organization; OrganizationForum keys in the attributes become its
     * forum settings.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function createForumOrganization(array $attributes = []): Organization
    {
        $forum = array_intersect_key($attributes, array_flip(OrganizationForum::SETTINGS));
        $organization = Organization::factory()->create(array_diff_key($attributes, $forum));

        OrganizationForum::store($organization->id, $forum);

        return $organization;
    }
}
