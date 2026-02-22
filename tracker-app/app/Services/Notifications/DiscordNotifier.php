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
     * Resolve a Discord role mention for a squad or organization.
     *
     * Attempts to resolve a role mention by looking up the squad/organization in the
     * configured squad_roles mapping. First tries exact key matches (case-insensitive),
     * then attempts partial substring matches. Falls back to the default mention if no
     * match is found or if the squad parameter is null/empty.
     *
     * @param int|string|null $squad The squad ID (int), organization name (string), or null.
     * @return string|null The Discord role mention string (e.g., "<@&123456>"), or null if no mention is configured.
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
     * Send an event notification to Discord via webhook.
     *
     * Composes an embedded Discord message with the event details and sends it to the
     * configured webhook URL. The message includes a squad/organization mention (if configured),
     * event title, description, a direct link to the event, and timestamp. Returns false if
     * no webhook is configured.
     *
     * @param int $event_id The ID of the event being notified about.
     * @param string $title The event title to display in the Discord message.
     * @param string|null $description Optional event description displayed in the embed.
     * @param int|string|null $squad The squad ID or organization name for role mention resolution.
     * @return bool True if the webhook was posted successfully, false if no webhook is configured.
     */
    public function sendEventNotification(int $event_id, string $title, ?string $description = null, int|string|null $squad = null): bool
    {
        $webhook = config('discord.webhooks.default') ?? config('discord.webhook_url');

        if (empty($webhook))
        {
            return false;
        }

        $mention = $this->getSquadMention($squad);

        $content = trim(($mention ? $mention . ' ' : '') . "$title has been posted.");

        $payload = [
            'content' => $content,
            'username' => config('app.name') . ' Bot',
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
