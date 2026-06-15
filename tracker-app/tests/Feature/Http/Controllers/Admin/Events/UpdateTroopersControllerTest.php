<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_troopers_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.troopers');
    }

    public function test_invoke_shows_all_member_clubs_for_handler_costume(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event_org = Organization::factory()->create();
        $other_member_org = Organization::factory()->create();
        $event_org->update([Organization::NODE_PATH => (string) $event_org->id]);
        $other_member_org->update([Organization::NODE_PATH => (string) $other_member_org->id]);

        $event = Event::factory()->withOrganization($event_org)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $handler_costume = Costume::factory()->withName(Costume::HANDLER)->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $event_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($event_org)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($other_member_org)->asMember()->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($event_org)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($other_member_org)->create();

        EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->withCostume($handler_costume)
            ->asGoing()
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertOk();

        $event_trooper = $response->viewData('event_shifts')->first()->event_troopers->first();
        $this->assertTrue($event_trooper->org_options->contains('id', $event_org->id));
        $this->assertTrue($event_trooper->org_options->contains('id', $other_member_org->id));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.troopers', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
