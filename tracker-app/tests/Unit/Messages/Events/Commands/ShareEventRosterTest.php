<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Mail\Events\ShareEventRoster as ShareEventRosterMail;
use App\Messages\Events\Commands\ShareEventRoster;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShareEventRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_event_share_with_uuid_token(): void
    {
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRoster(
            event: $event,
            recipient_email: $recipient_email,
            shared_by_trooper: $trooper
        );

        $command->handle();

        $share = EventShare::where(EventShare::EVENT_ID, $event->id)->first();
        $this->assertNotNull($share);
        $this->assertNotEmpty($share->{EventShare::SHARE_TOKEN});
        $this->assertEquals(36, strlen($share->{EventShare::SHARE_TOKEN}));
    }

    public function test_handle_sets_expiration_to_one_day_after_event_end(): void
    {
        Mail::fake();

        $event = Event::factory()->create([
            Event::EVENT_END => now()->addDays(7),
        ]);
        $trooper = Trooper::factory()->create();

        $command = new ShareEventRoster(
            event: $event,
            recipient_email: 'test@example.com',
            shared_by_trooper: $trooper
        );

        $command->handle();

        $share = EventShare::where(EventShare::EVENT_ID, $event->id)->first();
        $expected_expiration = $event->{Event::EVENT_END}->addDay();

        $this->assertEquals(
            $expected_expiration->format('Y-m-d H:i'),
            $share->{EventShare::EXPIRES_AT}->format('Y-m-d H:i')
        );
    }

    public function test_handle_stores_recipient_email_and_trooper_id(): void
    {
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRoster(
            event: $event,
            recipient_email: $recipient_email,
            shared_by_trooper: $trooper
        );

        $command->handle();

        $this->assertDatabaseHas('tt_event_shares', [
            EventShare::EVENT_ID => $event->id,
            EventShare::TROOPER_ID => $trooper->id,
            EventShare::RECIPIENT_EMAIL => $recipient_email,
        ]);
    }

    public function test_handle_queues_share_email_to_recipient_with_cc_to_sharer(): void
    {
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'sharer@example.com',
        ]);
        $recipient_email = 'coordinator@example.com';

        $command = new ShareEventRoster(
            event: $event,
            recipient_email: $recipient_email,
            shared_by_trooper: $trooper
        );

        $command->handle();

        Mail::assertQueued(ShareEventRosterMail::class, function ($mail) use ($recipient_email, $trooper)
        {
            return $mail->hasTo($recipient_email) && $mail->hasCc($trooper->{Trooper::EMAIL});
        });
    }

    public function test_handle_returns_null(): void
    {
        Mail::fake();

        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();

        $command = new ShareEventRoster(
            event: $event,
            recipient_email: 'test@example.com',
            shared_by_trooper: $trooper
        );

        $result = $command->handle();

        $this->assertNull($result);
    }
}
