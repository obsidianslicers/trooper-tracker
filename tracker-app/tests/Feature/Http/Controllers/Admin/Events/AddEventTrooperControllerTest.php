<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddEventTrooperControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_adds_trooper_to_event_shift(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($admin)->post(
            route('admin.events.troopers.add', compact('event', 'event_shift')),
            ['trooper_id' => $trooper->id]
        );

        $response->assertNoContent();
        $response->assertHeader('HX-Redirect');
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_invoke_adds_trooper_with_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $response = $this->actingAs($admin)->post(
            route('admin.events.troopers.add', compact('event', 'event_shift')),
            ['trooper_id' => $trooper->id, 'costume_id' => $costume->id]
        );

        $response->assertNoContent();
        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::COSTUME_ID => $costume->id,
        ]);
    }

    public function test_invoke_returns_no_content_if_trooper_already_signed_up(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->create();

        $response = $this->actingAs($admin)->post(
            route('admin.events.troopers.add', compact('event', 'event_shift')),
            ['trooper_id' => $trooper->id]
        );

        $response->assertNoContent();
        $response->assertHeader('HX-Redirect');
        $this->assertDatabaseCount('tt_event_troopers', 1);
    }

    public function test_invoke_returns_404_if_shift_not_in_event(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($other_event)->create();

        $response = $this->actingAs($admin)->post(
            route('admin.events.troopers.add', compact('event', 'event_shift')),
            ['trooper_id' => $trooper->id]
        );

        $response->assertNotFound();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->post(
            route('admin.events.troopers.add', compact('event', 'event_shift')),
            ['trooper_id' => 1]
        );

        $response->assertRedirect(route('auth.login'));
    }
}
