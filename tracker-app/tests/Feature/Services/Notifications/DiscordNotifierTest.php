<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Notifications;

use App\Models\Organization;
use App\Services\Notifications\DiscordNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_squad_mention_returns_organization_discord_mention(): void
    {
        $organization = Organization::factory()->create([
            Organization::DISCORD_MENTION => '<@&12345>',
        ]);

        $subject = new DiscordNotifier;

        $this->assertSame('<@&12345>', $subject->getSquadMention($organization));
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
    }

    public function test_send_event_notification_returns_false_without_webhook(): void
    {
        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);

        $subject = new DiscordNotifier;

        $this->assertFalse($subject->sendEventNotification(1, 'Event Title'));
    }

    public function test_send_event_notification_posts_payload_to_webhook(): void
    {
        config(['discord.webhooks.default' => 'https://discord.test/webhook']);

        Http::fake();

        $organization = Organization::factory()->create([
            Organization::DISCORD_MENTION => '<@&12345>',
        ]);

        $subject = new DiscordNotifier;

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
}
