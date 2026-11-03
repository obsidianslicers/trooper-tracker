<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord;

use App\Events\EventPosted;
use App\Models\Organization;
use App\Plugins\PluginForms;
use App\Plugins\PluginServiceProvider;
use TroopTracker\Plugins\Discord\Console\VerifyMentionsCommand;
use TroopTracker\Plugins\Discord\Listeners\AnnounceEventOnDiscord;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

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

        $this->addOrganizationMentionField();

        $this->addSystemCheck(
            'Discord webhook',
            fn (): bool => $this->app->make(DiscordNotifier::class)->isConfigured(),
        );

        $this->commands([VerifyMentionsCommand::class]);
    }

    private function addOrganizationMentionField(): void
    {
        $this->addToSlot(
            'admin.organizations.update.fields',
            'discord::organization-mention-field',
            fn (array $context): array => [
                'discord_mention' => DiscordOrganizationMention::forOrganization(
                    $context['organization']->id
                ),
            ],
        );

        $this->extendForm(
            PluginForms::ORGANIZATION_UPDATE,
            rules: ['discord_mention' => ['nullable', 'string', 'max:128']],
            saved: function (Organization $organization, array $validated): void
            {
                if (array_key_exists('discord_mention', $validated))
                {
                    DiscordOrganizationMention::store(
                        $organization->id,
                        $validated['discord_mention'],
                    );
                }
            },
        );
    }
}
