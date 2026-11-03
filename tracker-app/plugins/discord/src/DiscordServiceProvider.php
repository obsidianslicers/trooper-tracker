<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord;

use App\Events\EventPosted;
use App\Plugins\PluginServiceProvider;
use TroopTracker\Plugins\Discord\Listeners\AnnounceEventOnDiscord;

class DiscordServiceProvider extends PluginServiceProvider
{
    protected function registerPlugin(): void
    {
        $this->app->bind(DiscordNotifier::class, fn (): DiscordNotifier => new DiscordNotifier(
            webhook_url: $this->setting('webhook_url') ?: config('plugins.discord.webhook_url'),
        ));
    }

    public function bootPlugin(): void
    {
        $this->listen(EventPosted::class, AnnounceEventOnDiscord::class);
    }
}
