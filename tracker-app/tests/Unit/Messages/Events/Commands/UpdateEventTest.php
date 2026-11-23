<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Jobs\ReconcileEventRosterJob;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Messages\Events\Commands\UpdateEvent;
use App\Messages\Events\Commands\UpdateEventDetails;
use App\Messages\Events\Commands\UpdateEventOrganizations;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class UpdateEventTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    private Trooper $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->admin = Trooper::factory()->asSystemOwner()->create();
    }

    public function test_handle_saves_details_and_organizations_then_reconciles_the_roster(): void
    {
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);
        $messages = $this->fakeMessages([
            UpdateEventDetails::class => null,
            UpdateEventOrganizations::class => null,
        ]);
        $organizations = [['id' => 1]];

        $subject = new UpdateEvent($event, ['status' => 'open', 'organizations' => $organizations], $this->admin);
        $subject->handle();

        $messages->assertCalled(UpdateEventDetails::class);
        $messages->assertCalled(
            UpdateEventOrganizations::class,
            fn (array $params): bool => $params['data'] === $organizations
        );
        Queue::assertPushed(ReconcileEventRosterJob::class);
        Queue::assertNotPushed(SendEventCreatedNotificationsJob::class);
        Queue::assertNotPushed(SendEventCancelledNotificationsJob::class);
    }

    public function test_handle_announces_a_draft_when_it_opens(): void
    {
        $event = Event::factory()->create([Event::STATUS => EventStatus::DRAFT]);

        $subject = new UpdateEvent($event, $this->form($event, EventStatus::OPEN), $this->admin);
        $subject->handle();

        Queue::assertPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_handle_tells_the_roster_when_an_open_event_is_cancelled(): void
    {
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);

        $subject = new UpdateEvent($event, $this->form($event, EventStatus::CANCELLED), $this->admin);
        $subject->handle();

        Queue::assertPushed(SendEventCancelledNotificationsJob::class);
    }

    public function test_handle_sends_nothing_when_a_draft_is_cancelled(): void
    {
        $event = Event::factory()->create([Event::STATUS => EventStatus::DRAFT]);

        $subject = new UpdateEvent($event, $this->form($event, EventStatus::CANCELLED), $this->admin);
        $subject->handle();

        Queue::assertNotPushed(SendEventCreatedNotificationsJob::class);
        Queue::assertNotPushed(SendEventCancelledNotificationsJob::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function form(Event $event, EventStatus $status): array
    {
        return [
            Event::NAME => $event->name,
            Event::STATUS => $status->value,
        ];
    }
}
