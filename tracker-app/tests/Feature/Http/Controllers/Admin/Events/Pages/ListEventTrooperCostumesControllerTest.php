<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events\Pages;

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

class ListEventTrooperCostumesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_approved_costumes_for_trooper(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
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
        $response->assertJsonPath('trooper_id', $trooper->id);

        $costume_ids = collect($response->json('costume_options'))->pluck('value');
        $this->assertTrue($costume_ids->contains($approved_costume->id));
        $this->assertFalse($costume_ids->contains($unapproved_costume->id));
    }

    public function test_invoke_renders_eligible_organizations_for_trooper(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
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
        $organization_ids = collect($response->json('organization_options'))->pluck('value');
        $this->assertTrue($organization_ids->contains($eligible_organization->id));
        $this->assertFalse($organization_ids->contains($ineligible_organization->id));
    }

    public function test_invoke_returns_404_for_shift_from_different_event(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
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
