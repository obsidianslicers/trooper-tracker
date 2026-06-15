<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_statuses_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [],
        ]);

        $response->assertRedirect(route('admin.events.troopers', ['event' => $event->id]));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/'.$event->id.'/troopers', []);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_saves_submitted_org_selection_when_costume_set(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume1 = OrganizationCostume::factory()->forOrganization($org1)->forCostume($costume)->create();
        $org_costume2 = OrganizationCostume::factory()->forOrganization($org2)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume1)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume2)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_ids' => [$org1->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$org1->id], $event_trooper->costume_organization_ids);
    }

    public function test_invoke_observer_strips_ineligible_org_on_save(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $eligible_org = Organization::factory()->create();
        $ineligible_org = Organization::factory()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $eligible_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $ineligible_org->id,
            EventOrganization::CAN_ATTEND => true,
        ]);

        $org_costume = OrganizationCostume::factory()->forOrganization($eligible_org)->forCostume($costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $this->actingAs($admin)->post('/admin/events/'.$event->id.'/troopers', [
            'troopers' => [
                $event_trooper->id => [
                    'status' => 'going',
                    'costume_id' => $costume->id,
                    'organization_ids' => [$ineligible_org->id],
                ],
            ],
        ]);

        $event_trooper->refresh();
        $this->assertSame([$eligible_org->id], $event_trooper->costume_organization_ids);
    }
}
