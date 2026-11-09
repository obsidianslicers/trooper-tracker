<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventStatus;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_validation_errors_for_invalid_payload(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/update', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/'.$event->id.'/update', []);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_flows_status_to_shifts_when_transitioning_from_draft_to_open(): void
    {
        Queue::fake([SendEventCreatedNotificationsJob::class]);

        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::DRAFT])->create();
        $shift = EventShift::factory()->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::DRAFT])
            ->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/update', [
            'name' => 'Imperial Muster',
            'type' => 'regular',
            'status' => EventStatus::OPEN->value,
            'organization_id' => $event->organization_id,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_event_shifts', [
            'id' => $shift->id,
            'status' => EventStatus::OPEN->value,
        ]);
    }

    public function test_invoke_cancels_shifts_when_event_is_cancelled(): void
    {
        Queue::fake([SendEventCancelledNotificationsJob::class]);

        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/update', [
            'name' => 'Imperial Muster',
            'type' => 'regular',
            'status' => EventStatus::CANCELLED->value,
            'organization_id' => $event->organization_id,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
        ]);

        $response->assertRedirect();
    }

    public function test_invoke_cancels_active_event_troopers_when_event_is_cancelled(): void
    {
        Queue::fake([SendEventCancelledNotificationsJob::class]);

        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $shift = EventShift::factory()->forEvent($event)
            ->state([EventShift::STATUS => EventStatus::OPEN])
            ->create();
        $event_trooper = EventTrooper::factory()->forEventShift($shift)->asGoing()->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/update', [
            'name' => 'Imperial Muster',
            'type' => 'regular',
            'status' => EventStatus::CANCELLED->value,
            'organization_id' => $event->organization_id,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
        ]);

        $response->assertRedirect();
    }

    public function test_invoke_reassigns_hosting_organization(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->state([Event::STATUS => EventStatus::OPEN])->create();
        $new_host = Organization::factory()->create();

        $event_start = now()->addDays(7);
        $event_end = now()->addDays(7)->addHours(4);

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/update', [
            'name' => 'Imperial Muster',
            'type' => 'regular',
            'status' => EventStatus::OPEN->value,
            'organization_id' => $new_host->id,
            'event_start' => $event_start->format('Y-m-d H:i:s'),
            'event_end' => $event_end->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tt_events', [
            'id' => $event->id,
            'organization_id' => $new_host->id,
            'primary_organization_id' => $new_host->getPrimaryClub()->id,
        ]);
    }

    public function test_invoke_rejects_hosting_organization_not_moderated_by_user(): void
    {
        $organization = Organization::factory()->withNodePath('100.')->create();
        $other_organization = Organization::factory()->withNodePath('200.')->create();

        $trooper = Trooper::factory()->asModerator()->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asModerator()
            ->create();

        $event = Event::factory()
            ->state([Event::ORGANIZATION_ID => $organization->id])
            ->create();

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/update', [
            'name' => 'Imperial Muster',
            'type' => 'regular',
            'status' => EventStatus::OPEN->value,
            'organization_id' => $other_organization->id,
            'event_start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'event_end' => now()->addDays(7)->addHours(4)->format('Y-m-d H:i:s'),
            'tentative_signups_allowed' => false,
            'secure_staging_area' => false,
            'allow_blasters' => false,
            'allow_props' => false,
            'parking_available' => false,
            'accessible' => false,
        ]);

        $response->assertSessionHasErrors(Event::ORGANIZATION_ID);
    }

    public function test_invoke_keeps_an_event_type_hidden_since_the_event_was_made(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $hidden = EventType::factory()->inactive()->create();
        $other_hidden = EventType::factory()->inactive()->create();
        $event = Event::factory()->state([Event::TYPE => $hidden->slug])->create();

        $this->actingAs($trooper)
            ->post('/admin/events/'.$event->id.'/update', $this->typePayload($event, $hidden->slug))
            ->assertSessionHasNoErrors();

        $this->actingAs($trooper)
            ->post('/admin/events/'.$event->id.'/update', $this->typePayload($event, $other_hidden->slug))
            ->assertSessionHasErrors('type');

        $this->assertSame($hidden->slug, $event->fresh()->type);
    }

    /**
     * @return array<string, mixed>
     */
    private function typePayload(Event $event, string $type): array
    {
        return [
            'name' => 'Imperial Muster',
            'type' => $type,
            'status' => EventStatus::DRAFT->value,
            'organization_id' => $event->organization_id,
            'event_start' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'event_end' => now()->addDays(7)->addHours(4)->format('Y-m-d H:i:s'),
        ];
    }
}
