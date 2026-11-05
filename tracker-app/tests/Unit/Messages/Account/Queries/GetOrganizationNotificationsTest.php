<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\Queries;

use App\Messages\Account\Queries\GetOrganizationNotifications;
use App\Messages\Account\Resources\OrganizationNotificationCollection;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetOrganizationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_enables_only_assignments_set_to_notify(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($organization)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)
            ->withShouldNotify(false)->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($unit)
            ->withShouldNotify()->create();

        $subject = new GetOrganizationNotifications($trooper);
        $result = $subject->handle();

        $loaded_organization = $result->firstWhere('id', $organization->id);
        $loaded_region = $loaded_organization->organizations->first();
        $this->assertFalse($loaded_organization->enabled);
        $this->assertFalse($loaded_region->enabled);
        $this->assertTrue($loaded_region->organizations->first()->enabled);
    }

    public function test_collection_maps_enabled_flags_through_the_hierarchy(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($organization)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($region)
            ->withShouldNotify()->create();

        $collection = GetOrganizationNotifications::call(trooper: $trooper);
        $result = (new OrganizationNotificationCollection($collection))->toArray(new Request);

        $this->assertFalse($result[0]['enabled']);
        $this->assertTrue($result[0]['regions'][0]['enabled']);
        $this->assertSame($region->name, $result[0]['regions'][0]['name']);
        $this->assertSame(
            ['id' => $unit->id, 'name' => $unit->name, 'enabled' => false],
            $result[0]['regions'][0]['units'][0]
        );
    }
}
