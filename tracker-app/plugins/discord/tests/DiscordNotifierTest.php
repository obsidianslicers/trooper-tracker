<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Tests;

use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\DiscordNotifier;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

class DiscordNotifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $plugins = app(PluginManager::class);
        $plugins->enable('discord');
        $plugins->load($plugins->findOrFail('discord'));
    }

    public function test_get_squad_mention_returns_the_organization_mention(): void
    {
        $organization = $this->organizationWithMention('Garrison', '<@&12345>');

        $subject = new DiscordNotifier;

        $this->assertSame('<@&12345>', $subject->getSquadMention($organization));
        $this->assertSame('<@&12345>', $subject->getSquadMention($organization->id));
    }

    public function test_get_squad_mention_matches_organization_names(): void
    {
        $this->organizationWithMention('501st', '<@&501>');

        $subject = new DiscordNotifier;

        $this->assertSame('<@&501>', $subject->getSquadMention('501ST'));
        $this->assertSame('<@&501>', $subject->getSquadMention('501st Legion'));
    }

    public function test_get_squad_mention_ignores_a_cleared_mention(): void
    {
        $organization = $this->organizationWithMention('Garrison', '<@&12345>');
        DiscordOrganizationMention::store($organization->id, '');

        $this->assertNull((new DiscordNotifier)->getSquadMention($organization));
    }

    public function test_get_squad_mention_accepts_preformatted_mention_string(): void
    {
        $subject = new DiscordNotifier;

        $this->assertSame('<@&99999>', $subject->getSquadMention('<@&99999>'));
    }

    public function test_get_squad_mention_returns_null_when_no_match_found(): void
    {
        $subject = new DiscordNotifier;

        $this->assertNull($subject->getSquadMention('unknown-squad'));
        $this->assertNull($subject->getSquadMention(null));
    }

    public function test_send_event_notification_returns_false_without_webhook(): void
    {
        $subject = new DiscordNotifier(webhook_url: null);

        $this->assertFalse($subject->sendEventNotification(1, 'Event Title'));
    }

    public function test_send_event_notification_posts_payload_to_webhook(): void
    {
        Http::fake();

        $organization = $this->organizationWithMention('Garrison', '<@&12345>');

        $subject = new DiscordNotifier(webhook_url: 'https://discord.test/webhook');

        $result = $subject->sendEventNotification(5, 'Troop Alpha', 'Desc', $organization);

        $this->assertTrue($result);

        Http::assertSent(function ($request)
        {
            $data = $request->data();

            return $request->url() === 'https://discord.test/webhook'
                && str_contains((string) ($data['content'] ?? ''), '<@&12345>')
                && ($data['embeds'][0]['url'] ?? null) === route('events.display', ['event' => 5]);
        });
    }

    public function test_send_event_notification_says_when_the_event_does_not_count(): void
    {
        Http::fake();

        $subject = new DiscordNotifier(webhook_url: 'https://discord.test/webhook');

        $subject->sendEventNotification(5, 'Armor build night', null, null, 'Non-troop-credit event: Does not count toward troop totals.');

        Http::assertSent(fn ($request) => $request->data()['content']
            === "Armor build night has been posted.\nℹ️ **Non-troop-credit event: Does not count toward troop totals.**");
    }

    public function test_send_event_notification_adds_nothing_for_a_counted_event(): void
    {
        Http::fake();

        (new DiscordNotifier(webhook_url: 'https://discord.test/webhook'))->sendEventNotification(5, 'Hospital visit');

        Http::assertSent(fn ($request) => $request->data()['content'] === 'Hospital visit has been posted.');
    }

    private function organizationWithMention(string $name, string $mention): Organization
    {
        $organization = Organization::factory()->create([Organization::NAME => $name]);
        DiscordOrganizationMention::store($organization->id, $mention);

        return $organization;
    }
}
