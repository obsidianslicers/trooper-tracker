<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Commands;

use App\Features\Events\Commands\ShareEventRosterCommand;
use App\Features\Events\Commands\ShareEventRosterCommandHandler;
use App\Mail\Events\ShareEventRoster;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Unit tests for ShareEventRosterCommandHandler.
 *
 * Verifies:
 * - Creates EventShare record with correct attributes
 * - Generates unique UUID share token
 * - Sets expiration to one day after event ends
 * - Sends email to recipient with CC to sharing trooper
 */
class ShareEventRosterCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_event_share(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result);
        $this->assertDatabaseHas('tt_event_shares', [
            EventShare::EVENT_ID => $event->id,
            EventShare::TROOPER_ID => $shared_by_trooper->id,
            EventShare::RECIPIENT_EMAIL => $recipient_email,
        ]);
    }

    public function test_invoke_generates_uuid_share_token(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $subject($command);

        // Assert
        $share = EventShare::where(EventShare::EVENT_ID, $event->id)->first();
        $this->assertNotNull($share->share_token);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $share->share_token
        );
    }

    public function test_invoke_sets_expiration_to_one_day_after_event_ends(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $subject($command);

        // Assert
        $share = EventShare::where(EventShare::EVENT_ID, $event->id)->first();
        $this->assertEquals(
            $event->event_end->addDay()->format('Y-m-d H:i:s'),
            $share->expires_at->format('Y-m-d H:i:s')
        );
    }

    public function test_invoke_queues_email_to_recipient(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $subject($command);

        // Assert
        Mail::assertQueued(ShareEventRoster::class, function ($mail) use ($recipient_email)
        {
            return $mail->hasTo($recipient_email);
        });
    }

    public function test_invoke_cc_sharing_trooper_on_email(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'sharer@example.com',
        ]);
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $subject($command);

        // Assert
        Mail::assertQueued(ShareEventRoster::class, function ($mail) use ($shared_by_trooper)
        {
            return $mail->hasCc($shared_by_trooper->email);
        });
    }

    public function test_invoke_stores_recipient_email(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create();
        $recipient_email = 'specific-coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $subject($command);

        // Assert
        $share = EventShare::where(EventShare::EVENT_ID, $event->id)->first();
        $this->assertEquals($recipient_email, $share->recipient_email);
    }

    public function test_invoke_initializes_view_count_to_zero(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $subject($command);

        // Assert
        $share = EventShare::where(EventShare::EVENT_ID, $event->id)->first();
        $this->assertEquals(0, $share->view_count);
    }

    public function test_invoke_initializes_is_revoked_to_false(): void
    {
        // Arrange
        Mail::fake();

        $event = Event::factory()->create();
        $shared_by_trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRosterCommand($event, $recipient_email, $shared_by_trooper);
        $subject = new ShareEventRosterCommandHandler();

        // Act
        $subject($command);

        // Assert
        $share = EventShare::where(EventShare::EVENT_ID, $event->id)->first();
        $this->assertFalse($share->is_revoked);
    }
}
