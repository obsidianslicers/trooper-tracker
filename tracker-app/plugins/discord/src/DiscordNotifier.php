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

        return is_string($squad) ? $this->mentionForText(trim($squad)) : null;
    }

    private function mentionForText(string $search): ?string
    {
        if ($search === '' || preg_match('/^<@&\d+>$/', $search))
        {
            return $search === '' ? null : $search;
        }

        $organization = $this->findOrganizationByName($search);

        if ($organization === null)
        {
            return null;
        }

        return DiscordOrganizationMention::forOrganization($organization->id);
    }

    private function findOrganizationByName(string $search): ?Organization
    {
        $exact = Organization::whereRaw('LOWER(name) = ?', [strtolower($search)])
            ->orderBy(Organization::ID, 'desc')
            ->first();

        if ($exact !== null)
        {
            return $exact;
        }

        // Matched in PHP rather than SQL to stay portable across database drivers.
        $with_mention = DiscordOrganizationMention::query()
            ->where(DiscordOrganizationMention::MENTION, '!=', '')
            ->select(DiscordOrganizationMention::ORGANIZATION_ID);

        $candidates = Organization::query()
            ->whereIn(Organization::ID, $with_mention)
            ->orderBy(Organization::ID, 'desc')
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
     * @param  Organization|int|string|null  $squad  The organization (preferred), its ID, or a name.
     * @return bool True when the webhook was posted, false when no webhook is configured.
     */
    public function sendEventNotification(
        int $event_id,
        string $title,
        ?string $description = null,
        Organization|int|string|null $squad = null,
    ): bool {
        if (!$this->isConfigured())
        {
            return false;
        }

        $mention = $this->getSquadMention($squad);
        $content = trim(($mention ? $mention.' ' : '')."$title has been posted.");

        Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($this->webhook_url, [
                'content' => $content,
                'username' => config('app.name').' Bot',
                'tts' => false,
                'embeds' => [$this->eventEmbed($event_id, $title, $description)],
            ]);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function eventEmbed(int $event_id, string $title, ?string $description): array
    {
        return [
            'title' => $title,
            'type' => 'rich',
            'description' => $description,
            'url' => route('events.display', ['event' => $event_id]),
            'timestamp' => now()->toIso8601String(),
            'color' => hexdec('3366ff'),
        ];
    }
}
