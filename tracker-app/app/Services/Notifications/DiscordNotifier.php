<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * @param  int|string|null  $squad  The squad ID (int), organization name (string), or null.
     * @return string|null The Discord role mention string (e.g., "<@&123456>"), or null if no mention is configured.
     */
    public function getSquadMention(int|string|null $squad): ?string
    {
        // If caller already provided a mention string like <@&123>, return it unchanged.
        if (is_string($squad) && preg_match('/^<@&\d+>$/', trim($squad))) {
            return trim($squad);
        }

        // Prefer DB-driven discord_mention on Organization records.
        $org = null;

        if (is_int($squad)) {
            $org = Organization::find($squad);
        } elseif (is_string($squad) && $squad !== '') {
            $search = trim($squad);

            // exact (case-insensitive) match — prefer most recently created org
            $org = Organization::whereRaw('LOWER(name) = ?', [strtolower($search)])->orderBy('id', 'desc')->first();

            // fallback to partial match: try to find orgs where the org name
            // appears inside the provided search string (e.g. "501st Legion"
            // should match org name "501st") or where the search contains the
            // org name. Load candidates and check in-PHP to keep compatibility
            // across DB drivers.
            if (! $org) {
                $candidates = Organization::whereNotNull('discord_mention')->orderBy('id', 'desc')->get();

                foreach ($candidates as $candidate) {
                    $candidateName = strtolower($candidate->name);
                    if ($candidateName === '') {
                        continue;
                    }

                    if (str_contains(strtolower($search), $candidateName) || str_contains($candidateName, strtolower($search))) {
                        $org = $candidate;
                        break;
                    }
                }
            }
        }

        if ($org && ! empty($org->discord_mention)) {
            return $org->discord_mention;
        }

        // No DB mention found — do not fall back to config-based mappings anymore.
        return null;
    }

    /**
     * Send an event notification to Discord via webhook.
     *
     * Composes an embedded Discord message with the event details and sends it to the
     * configured webhook URL. The message includes a squad/organization mention (if configured),
     * event title, description, a direct link to the event, and timestamp. Returns false if
     * no webhook is configured.
     *
     * @param  int  $event_id  The ID of the event being notified about.
     * @param  string  $title  The event title to display in the Discord message.
     * @param  string|null  $description  Optional event description displayed in the embed.
     * @param  int|string|null  $squad  The squad ID or organization name for role mention resolution.
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
