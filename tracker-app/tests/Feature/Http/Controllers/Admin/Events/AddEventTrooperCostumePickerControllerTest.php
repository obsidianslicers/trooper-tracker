<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddEventTrooperCostumePickerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_approved_costumes_for_trooper(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $approved_costume = Costume::factory()->create();
        $unapproved_costume = Costume::factory()->create();

        $org_costume = OrganizationCostume::factory()->forCostume($approved_costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.troopers.costume-picker', [
            'event' => $event->id,
            'event_shift' => $event_shift->id,
            'trooper_id' => $trooper->id,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.inc.add-trooper-costume-picker');
        $response->assertSee('hx-params="trooper_id,costume_id,organization_id"', false);
        $response->assertViewHas('costumes', function (array $costumes) use ($approved_costume, $unapproved_costume): bool {
            return array_key_exists($approved_costume->id, $costumes)
                && ! array_key_exists($unapproved_costume->id, $costumes);
        });
    }

    public function test_invoke_renders_eligible_organizations_for_trooper(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $eligible_organization = Organization::factory()->create();
        $ineligible_organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($eligible_organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($eligible_organization)
            ->canAttend()
            ->create();
        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($ineligible_organization)
            ->cannotAttend()
            ->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($eligible_organization)
            ->asMember()
            ->create();
        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($ineligible_organization)
            ->asMember()
            ->create();

        $response = $this->actingAs($admin)->get(route('admin.events.troopers.costume-picker', [
            'event' => $event->id,
            'event_shift' => $event_shift->id,
            'trooper_id' => $trooper->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('eligible_orgs', function ($eligible_orgs) use ($eligible_organization, $ineligible_organization): bool {
            return $eligible_orgs->pluck('id')->contains($eligible_organization->id)
                && !$eligible_orgs->pluck('id')->contains($ineligible_organization->id);
        });
    }

    public function test_invoke_returns_404_for_shift_from_different_event(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $other_event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($other_event)->create();

        $response = $this->actingAs($admin)->get(route('admin.events.troopers.costume-picker', [
            'event' => $event->id,
            'event_shift' => $event_shift->id,
            'trooper_id' => $trooper->id,
        ]));

        $response->assertNotFound();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->get(route('admin.events.troopers.costume-picker', [
            'event' => $event->id,
            'event_shift' => $event_shift->id,
            'trooper_id' => 1,
        ]));

        $response->assertRedirect(route('auth.login'));
    }
}
