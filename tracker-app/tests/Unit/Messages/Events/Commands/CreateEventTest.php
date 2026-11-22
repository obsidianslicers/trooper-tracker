<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Messages\Events\Commands\CreateEvent;
use App\Models\EventType;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_the_event_with_one_shift_and_its_clubs(): void
    {
        Queue::fake();
        $host = Organization::factory()->asOrganization()->create();
        $club = Organization::factory()->asOrganization()->create();

        $event = CreateEvent::call(data: $this->data($host, EventStatus::DRAFT, [
            $club->id => ['can_attend' => true, 'troopers_allowed' => 6, 'handlers_allowed' => null],
        ]));

        $this->assertSame('Imperial Muster', $event->name);
        $this->assertSame($host->id, $event->organization_id);

        $shift = $event->event_shifts()->sole();
        $this->assertTrue($shift->shift_starts_at->equalTo($event->event_start));
        $this->assertTrue($shift->shift_ends_at->equalTo($event->event_end));
        $this->assertSame(EventStatus::DRAFT, $shift->status);

        $this->assertSame(6, $event->organizations()->find($club->id)->pivot->troopers_allowed);
        Queue::assertNotPushed(SendEventCreatedNotificationsJob::class);
    }

    public function test_handle_tells_troopers_about_an_event_created_open(): void
    {
        Queue::fake();
        $host = Organization::factory()->asOrganization()->create();

        CreateEvent::call(data: $this->data($host, EventStatus::OPEN));

        Queue::assertPushed(SendEventCreatedNotificationsJob::class);
    }

    /**
     * @param  array<int, array<string, mixed>>  $organizations
     * @return array<string, mixed>
     */
    private function data(Organization $host, EventStatus $status, array $organizations = []): array
    {
        return [
            'organization_id' => $host->id,
            'name' => 'Imperial Muster',
            'event_type_id' => EventType::idNamed('Regular'),
            'status' => $status->value,
            'event_start' => now()->addDays(7)->startOfHour(),
            'event_end' => now()->addDays(7)->startOfHour()->addHours(4),
            'organizations' => $organizations,
        ];
    }
}
