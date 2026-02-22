<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

/**
 * Simple Discord notifier service.
 *
 * Responsible for composing and sending webhook messages. Kept small and
 * configuration-driven so other developers can easily modify mentions or
 * channel targets via `config/discord.php` or environment variables.
 */
final class DiscordNotifier
{
    /**
     * Accepts either an integer squad id or a string organization name.
     * Tries to resolve an exact numeric mapping first, then attempts
     * to match by name against configured mapping keys.
     */
    public function getSquadMention(int|string|null $squad): ?string
    {
        $squad_role_map = config('discord.squad_roles', []);

        // string name lookup: normalize and try exact key match first
        if (is_string($squad) && $squad !== '')
        {
            $search = strtolower($squad);

            // direct key matches (string keys in config)
            foreach ($squad_role_map as $k => $v)
            {
                if (!is_int($k))
                {
                    if (strtolower((string) $k) === $search)
                    {
                        return $v;
                    }
                }
            }

            // partial contains match: if the organization name contains a key
            foreach ($squad_role_map as $k => $v)
            {
                if (!is_int($k))
                {
                    $klower = strtolower((string) $k);
                    if ($klower !== '' && (str_contains($search, $klower) || str_contains($klower, $search)))
                    {
                        return $v;
                    }
                }
            }
        }

        return config('discord.default_mention');
    }

    /**
     * Accepts an event id, title, optional description, and a squad which
     * may be an integer id or a string organization name. The squad value
     * is resolved by `getSquadMention()` which handles both types.
     */
    public function sendEventNotification(int $event_id, string $title, ?string $description = null, int|string|null $squad = null): bool
    {
        $webhook = config('discord.webhooks.default') ?? config('discord.webhook_url');

        if (empty($webhook))
        {
            return false;
        }

        $mention = $this->getSquadMention($squad);

        $content = trim(($mention ? $mention.' ' : '')."$title has been posted.");

        $payload = [
            'content' => $content,
            'username' => config('app.name').' Bot',
            'tts' => false,
            'embeds' => [
                [
                    'title' => $title,
                    'type' => 'rich',
                    'description' => $description,
                    'url' => route('events.display', ['event' => $event_id]),
                    'timestamp' => now()->toIso8601String(),
                    'color' => hexdec('3366ff'),
                ],
            ],
        ];

        Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($webhook, $payload);

        return true;
    }
}
