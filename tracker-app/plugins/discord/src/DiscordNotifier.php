<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord;

use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

/**
 * Simple Discord notifier service.
 *
 * Responsible for composing and sending webhook messages. The webhook comes
 * from the plugin's settings, falling back to DISCORD_WEBHOOK_URL.
 */
final class DiscordNotifier
{
    public function __construct(private readonly ?string $webhook_url = null) {}

    public function isConfigured(): bool
    {
        return !empty($this->webhook_url);
    }

    /**
     * Resolve the Discord role mention for an organization.
     *
     * Accepts an Organization, its ID, a ready-made mention such as "<@&123>", or an
     * organization name. Names match exactly (case-insensitive) first, then by either
     * name containing the other (so "501st Legion" finds "501st").
     *
     * @param  Organization|int|string|null  $squad  The organization, its ID, a mention, or a name.
     * @return string|null The Discord mention (e.g. "<@&123456>"), or null when none is set.
     */
    public function getSquadMention(Organization|int|string|null $squad): ?string
    {
        if ($squad instanceof Organization)
        {
            return DiscordOrganizationMention::forOrganization($squad->id);
        }

        if (is_int($squad))
        {
            return DiscordOrganizationMention::forOrganization($squad);
        }

        if (!is_string($squad) || trim($squad) === '')
        {
            return null;
        }

        $search = trim($squad);

        if (preg_match('/^<@&\d+>$/', $search))
        {
            return $search;
        }

        $organization = $this->findOrganizationByName($search);

        return $organization === null ? null : DiscordOrganizationMention::forOrganization($organization->id);
    }

    private function findOrganizationByName(string $search): ?Organization
    {
        $exact = Organization::whereRaw('LOWER(name) = ?', [strtolower($search)])->orderBy('id', 'desc')->first();

        if ($exact !== null)
        {
            return $exact;
        }

        // Matched in PHP rather than SQL to stay portable across database drivers.
        $candidates = Organization::query()
            ->whereIn('id', DiscordOrganizationMention::query()->where('mention', '!=', '')->select('organization_id'))
            ->orderBy('id', 'desc')
            ->get();

        $needle = strtolower($search);

        return $candidates->first(function (Organization $candidate) use ($needle): bool
        {
            $name = strtolower($candidate->name);

            return $name !== '' && (str_contains($needle, $name) || str_contains($name, $needle));
        });
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
     * @param  Organization|int|string|null  $squad  Organization instance (preferred), squad ID, or org name for mention resolution.
     * @return bool True if the webhook was posted successfully, false if no webhook is configured.
     */
    public function sendEventNotification(int $event_id, string $title, ?string $description = null, Organization|int|string|null $squad = null): bool
    {
        if (!$this->isConfigured())
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
            ->post($this->webhook_url, $payload);

        return true;
    }
}
