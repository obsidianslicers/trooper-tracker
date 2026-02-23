<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Services\Notifications\DiscordNotifier;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for the DiscordNotifier service.
 *
 * Validates Discord webhook message composition and sending, including
 * squad/organization mention resolution and payload structure.
 */
class DiscordNotifierTest extends TestCase
{
    private DiscordNotifier $subject;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->subject = new DiscordNotifier();
    }

    /**
     * Test that getSquadMention returns default mention when squad is null.
     */
    public function test_get_squad_mention_returns_default_for_null_squad(): void
    {
        // Act
        $result = $this->subject->getSquadMention(null);

        // Assert: DB-driven behavior -> no mention configured => null
        $this->assertNull($result);
    }

    /**
     * Test that getSquadMention returns default mention when no config provided.
     */
    public function test_get_squad_mention_returns_default_when_no_match(): void
    {
        // Act
        $result = $this->subject->getSquadMention('unknown');

        // Assert: DB-driven behavior -> unknown org => null
        $this->assertNull($result);
    }

    /**
     * Test that getSquadMention finds exact string key match (case-insensitive).
     */
    public function test_get_squad_mention_finds_exact_string_match(): void
    {
        // Arrange: create DB org with mention
        Organization::factory()->create(['name' => 'alpha_squad', 'discord_mention' => '<@&111>']);

        // Act
        $result = $this->subject->getSquadMention('alpha_squad');

        // Assert
        $this->assertEquals('<@&111>', $result);
    }

    /**
     * Test that getSquadMention finds exact string match case-insensitively.
     */
    public function test_get_squad_mention_finds_exact_match_case_insensitive(): void
    {
        // Arrange
        Organization::factory()->create(['name' => 'Alpha_Squad', 'discord_mention' => '<@&111>']);

        // Act
        $result = $this->subject->getSquadMention('ALPHA_SQUAD');

        // Assert
        $this->assertEquals('<@&111>', $result);
    }

    /**
     * Test that getSquadMention finds partial substring match.
     */
    public function test_get_squad_mention_finds_partial_substring_match(): void
    {
        // Arrange: create DB org
        Organization::factory()->create(['name' => '501st', 'discord_mention' => '<@&111>']);

        // Act
        $result = $this->subject->getSquadMention('501st Legion');

        // Assert
        $this->assertEquals('<@&111>', $result);
    }

    /**
     * Test that sendEventNotification returns false when no webhook configured.
     */
    public function test_send_event_notification_returns_false_when_no_webhook(): void
    {
        // Arrange
        config(['discord.webhooks.default' => null, 'discord.webhook_url' => null]);

        // Act
        $result = $this->subject->sendEventNotification(1, 'Test Event');

        // Assert
        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    /**
     * Test that sendEventNotification sends HTTP request with correct payload.
     */
    public function test_send_event_notification_sends_webhook_with_correct_payload(): void
    {
        // Arrange
        $webhook_url = 'https://discord.com/api/webhooks/123456789/abcdefgh';
        config([
            'discord.webhooks.default' => $webhook_url,
            'discord.squad_roles' => [],
            'app.name' => 'Troop Tracker',
        ]);

        // Act
        $result = $this->subject->sendEventNotification(
            1,
            'Endor Troop',
            'A great event on Endor'
        );

        // Assert
        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($webhook_url)
        {
            $url = $request->url();
            $body = json_decode($request->body(), true);

            return $url === $webhook_url
                && $body['content'] === 'Endor Troop has been posted.'
                && $body['username'] === 'Troop Tracker Bot'
                && $body['tts'] === false
                && count($body['embeds']) === 1
                && $body['embeds'][0]['title'] === 'Endor Troop'
                && $body['embeds'][0]['description'] === 'A great event on Endor'
                && $body['embeds'][0]['type'] === 'rich'
                && $body['embeds'][0]['color'] === 3368703; // hexdec('3366ff')
        });
    }

    /**
     * Test that sendEventNotification includes squad mention in content.
     */
    public function test_send_event_notification_includes_squad_mention(): void
    {
        // Arrange
        $webhook_url = 'https://discord.com/api/webhooks/123456789/abcdefgh';
        config([
            'discord.webhooks.default' => $webhook_url,
            'app.name' => 'Troop Tracker',
        ]);

        // create a DB organization with the mention configured
        Organization::factory()->create(['name' => '501st', 'discord_mention' => '<@&777>']);

        // Act
        $result = $this->subject->sendEventNotification(
            1,
            'Event Title',
            null,
            '501st'
        );

        // Assert
        $this->assertTrue($result);

        Http::assertSent(function ($request) use ($webhook_url)
        {
            $body = json_decode($request->body(), true);

            return $request->url() === $webhook_url
                && $body['content'] === '<@&777> Event Title has been posted.';
        });
    }

    /**
     * Test that sendEventNotification works without description.
     */
    public function test_send_event_notification_works_without_description(): void
    {
        // Arrange
        $webhook_url = 'https://discord.com/api/webhooks/123456789/abcdefgh';
        config([
            'discord.webhooks.default' => $webhook_url,
            'discord.squad_roles' => [],
            'discord.default_mention' => null,
            'app.name' => 'Troop Tracker',
        ]);

        // Act
        $result = $this->subject->sendEventNotification(42, 'Event Title');

        // Assert
        $this->assertTrue($result);

        Http::assertSent(function ($request)
        {
            $body = json_decode($request->body(), true);

            return $body['content'] === 'Event Title has been posted.'
                && $body['embeds'][0]['description'] === null;
        });
    }

    /**
     * Test that sendEventNotification includes correct event link in embed.
     */
    public function test_send_event_notification_includes_event_link(): void
    {
        // Arrange
        $webhook_url = 'https://discord.com/api/webhooks/123456789/abcdefgh';
        config([
            'discord.webhooks.default' => $webhook_url,
            'discord.squad_roles' => [],
            'discord.default_mention' => null,
            'app.name' => 'Troop Tracker',
        ]);

        // Act
        $this->subject->sendEventNotification(99, 'Test Event');

        // Assert
        Http::assertSent(function ($request)
        {
            $body = json_decode($request->body(), true);
            $url = $body['embeds'][0]['url'];

            return !empty($url) && is_string($url);
        });
    }

    /**
     * Test that sendEventNotification includes timestamp in embed.
     */
    public function test_send_event_notification_includes_timestamp(): void
    {
        // Arrange
        $webhook_url = 'https://discord.com/api/webhooks/123456789/abcdefgh';
        config([
            'discord.webhooks.default' => $webhook_url,
            'discord.squad_roles' => [],
            'discord.default_mention' => null,
            'app.name' => 'Troop Tracker',
        ]);

        // Act
        $this->subject->sendEventNotification(1, 'Test');

        // Assert
        Http::assertSent(function ($request)
        {
            $body = json_decode($request->body(), true);
            $timestamp = $body['embeds'][0]['timestamp'];

            return !empty($timestamp) && str_contains($timestamp, 'T');
        });
    }

    /**
     * Test that sendEventNotification uses fallback webhook_url config.
     */
    public function test_send_event_notification_uses_fallback_webhook_url(): void
    {
        // Arrange
        $webhook_url = 'https://discord.com/api/webhooks/fallback/xyz';
        config([
            'discord.webhooks.default' => null,
            'discord.webhook_url' => $webhook_url,
            'discord.squad_roles' => [],
            'discord.default_mention' => null,
            'app.name' => 'Troop Tracker',
        ]);

        // Act
        $result = $this->subject->sendEventNotification(1, 'Test');

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) use ($webhook_url)
        {
            return $request->url() === $webhook_url;
        });
    }

    /**
     * Test that sendEventNotification trims content properly.
     */
    public function test_send_event_notification_trims_content(): void
    {
        // Arrange
        $webhook_url = 'https://discord.com/api/webhooks/123456789/abcdefgh';
        config([
            'discord.webhooks.default' => $webhook_url,
            'discord.squad_roles' => [],
            'discord.default_mention' => null,
            'app.name' => 'Troop Tracker',
        ]);

        // Act
        $this->subject->sendEventNotification(1, 'Event Title');

        // Assert
        Http::assertSent(function ($request)
        {
            $content = json_decode($request->body(), true)['content'];

            return $content === 'Event Title has been posted.' && $content === trim($content);
        });
    }
}

