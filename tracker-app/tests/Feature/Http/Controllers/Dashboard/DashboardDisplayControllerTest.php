<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_dashboard_for_authenticated_user(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $organization = Organization::factory()->create();
        $costume = OrganizationCostume::factory()->for($organization)->create();
        $event = Event::factory()->withOrganization($organization)->closed()->create();
        $event_shift = EventShift::factory()->withEvent($event)->closed()->create();

        EventTrooper::factory()
            ->withShift($event_shift)
            ->for($trooper)
            ->create([
                EventTrooper::COSTUME_ID => $costume->id,
            ]);

        // Act
        $response = $this->get(route('dashboard.display'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.display');
        $response->assertViewHas('trooper', function (Trooper $t) use ($trooper)
        {
            return $t->id === $trooper->id;
        });
        $response->assertViewHas('total_troops_by_organization', function ($collection)
        {
            return $collection->count() === 1 && $collection->first()->troop_count === 1;
        });
        $response->assertViewHas('total_troops_by_costume', function ($collection)
        {
            return $collection->count() === 1 && $collection->first()->troop_count === 1;
        });
    }

    public function test_invoke_displays_dashboard_for_another_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $other_trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $costume = OrganizationCostume::factory()->for($organization)->create();
        $event = Event::factory()->withOrganization($organization)->closed()->create();
        $event_shift = EventShift::factory()->withEvent($event)->closed()->create();

        EventTrooper::factory()
            ->withShift($event_shift)
            ->withTrooper($other_trooper)
            ->create([
                EventTrooper::COSTUME_ID => $costume->id,
            ]);

        // Act
        $response = $this->get(route('dashboard.display', ['trooper_id' => $other_trooper->id]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.display');
        $response->assertViewHas('trooper', function (Trooper $trooper) use ($other_trooper)
        {
            return $trooper->id === $other_trooper->id;
        });
    }

    public function test_invoke_redirects_if_trooper_not_found(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        // Act
        $response = $this->get(route('dashboard.display', ['trooper_id' => 999]));

        // Assert
        $response->assertNotFound();
    }

    public function test_invoke_correctly_calculates_troop_counts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $club1 = Organization::factory()->create();
        $costume1 = OrganizationCostume::factory()->for($club1)->create();

        $club2 = Organization::factory()->create();
        $costume3 = OrganizationCostume::factory()->for($club2)->create();

        $event_one = Event::factory()->withOrganization($club1)->closed()->create();
        $shift_one = EventShift::factory()->withEvent($event_one)->closed()->create();

        $event_two = Event::factory()->withOrganization($club2)->closed()->create();
        $shift_two = EventShift::factory()->withEvent($event_two)->closed()->create();

        EventTrooper::factory()->for($shift_one, 'event_shift')->for($trooper)->create([
            EventTrooper::COSTUME_ID => $costume1->id,
        ]);
        EventTrooper::factory()->for($shift_two, 'event_shift')->for($trooper)->create([
            EventTrooper::COSTUME_ID => $costume3->id,
        ]);

        // Act
        $response = $this->get(route('dashboard.display'));

        // Assert
        $response->assertViewHas('total_troops_by_organization', function ($collection)
        {
            return $collection->count() === 2;
        });
        $response->assertViewHas('total_troops_by_costume', function ($collection)
        {
            return $collection->count() === 2;
        });
    }
}
