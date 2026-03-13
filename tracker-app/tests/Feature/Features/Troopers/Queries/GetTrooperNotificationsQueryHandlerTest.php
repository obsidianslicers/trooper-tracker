<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperNotificationsQuery;
use App\Features\Troopers\Queries\GetTrooperNotificationsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperNotificationsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_marks_selected_nodes_in_hierarchy(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->withName('Org')->create();
        $region = Organization::factory()->asRegion()->withParent($organization)->withNodePath('100.200.')->withName('Region')->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->withNodePath('100.200.300.')->withName('Unit')->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->withShouldNotify()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($unit)->withShouldNotify()->create();

        $subject = new GetTrooperNotificationsQueryHandler();

        $result = $subject(new GetTrooperNotificationsQuery($trooper));

        $root = $result->firstWhere('id', $organization->id);
        $child_region = $root->organizations->firstWhere('id', $region->id);
        $child_unit = $child_region->organizations->firstWhere('id', $unit->id);

        $this->assertTrue($root->selected);
        $this->assertFalse($child_region->selected);
        $this->assertTrue($child_unit->selected);
    }
}
