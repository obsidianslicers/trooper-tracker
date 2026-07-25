<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveEventTrooperControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_event_trooper(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $response = $this->actingAs($admin)->post(
            route('admin.events.troopers.remove', compact('event', 'event_trooper'))
        );

        $response->assertNoContent();
        $response->assertHeader('HX-Redirect');
        $this->assertSoftDeleted('tt_event_troopers', ['id' => $event_trooper->id]);
    }

    public function test_invoke_promotes_next_stand_by_when_removing_going_trooper(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $event = Event::factory()->create([Event::ORGANIZATION_ID => $org->id]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($org)
            ->canAttend()
            ->create(['troopers_allowed' => 2]);

        $going_trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($going_trooper)
            ->asGoing()
            ->withSignedUpAt(Carbon::now()->subMinutes(10))
            ->create([
                EventTrooper::ORGANIZATION_ID => $org->id,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $stand_by_trooper = Trooper::factory()->asActive()->create();
        $stand_by_event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($stand_by_trooper)
            ->withSignedUpAt(Carbon::now()->subMinutes(5))
            ->create([
                EventTrooper::ORGANIZATION_ID => $org->id,
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::IS_HANDLER => false,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
                EventTrooper::ATTENDING_WITHOUT_COSTUME => true,
            ]);

        $this->actingAs($admin)->post(
            route('admin.events.troopers.remove', compact('event', 'event_trooper'))
        );

        $this->assertDatabaseHas('tt_event_troopers', [
            'id' => $stand_by_event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING->value,
        ]);
    }

    public function test_invoke_removing_cancelled_trooper_does_not_promote_stand_by(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $event = Event::factory()->create([Event::ORGANIZATION_ID => $org->id]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($org)
            ->canAttend()
            ->create(['troopers_allowed' => 2]);

        $cancelled_trooper = Trooper::factory()->asActive()->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($cancelled_trooper)
            ->create([
                EventTrooper::ORGANIZATION_ID => $org->id,
                EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $stand_by_event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create([
                EventTrooper::ORGANIZATION_ID => $org->id,
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $this->actingAs($admin)->post(
            route('admin.events.troopers.remove', compact('event', 'event_trooper'))
        );

        $this->assertDatabaseHas('tt_event_troopers', [
            'id' => $stand_by_event_trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_removing_stand_by_trooper_does_not_promote_another_stand_by(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $event = Event::factory()->create([Event::ORGANIZATION_ID => $org->id]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($org)
            ->canAttend()
            ->create(['troopers_allowed' => 2]);

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create([
                EventTrooper::ORGANIZATION_ID => $org->id,
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $next_stand_by = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->create([
                EventTrooper::ORGANIZATION_ID => $org->id,
                EventTrooper::STATUS => EventTrooperStatus::STAND_BY,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $this->actingAs($admin)->post(
            route('admin.events.troopers.remove', compact('event', 'event_trooper'))
        );

        $this->assertDatabaseHas('tt_event_troopers', [
            'id' => $next_stand_by->id,
            EventTrooper::STATUS => EventTrooperStatus::STAND_BY->value,
        ]);
    }

    public function test_invoke_returns_404_if_event_trooper_belongs_to_different_event(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $other_shift = EventShift::factory()->forEvent($other_event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($other_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $response = $this->actingAs($admin)->post(
            route('admin.events.troopers.remove', compact('event', 'event_trooper'))
        );

        $response->assertNotFound();
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $response = $this->post(
            route('admin.events.troopers.remove', compact('event', 'event_trooper'))
        );

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_forbids_member_without_update_permission(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create([
                EventTrooper::COSTUME_ID => null,
                EventTrooper::BACKUP_COSTUME_ID => null,
            ]);

        $response = $this->actingAs($member)->post(
            route('admin.events.troopers.remove', compact('event', 'event_trooper'))
        );

        $response->assertForbidden();
        $this->assertNotSoftDeleted('tt_event_troopers', ['id' => $event_trooper->id]);
    }
}
