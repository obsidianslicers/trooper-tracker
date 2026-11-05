<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Resources;

use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Messages\Organizations\Resources\OrganizationHierarchy;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrganizationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_nests_regions_and_units_under_their_organization(): void
    {
        $organization = Organization::factory()->asOrganization()->withName('501st')
            ->withIdentifierDisplay('TK')->create();
        $region = Organization::factory()->asRegion()->withParent($organization)
            ->withName('Florida')->create();
        $unit = Organization::factory()->asUnit()->withParent($region)
            ->withName('Everglades')->create();

        $subject = new OrganizationHierarchy(GetOrganizationHierarchy::call());
        $result = $subject->toArray(new Request);

        $this->assertCount(1, $result);
        $this->assertSame($organization->id, $result[0]['id']);
        $this->assertSame('TK', $result[0]['identifier_display']);
        $this->assertSame([
            'id' => $unit->id,
            'name' => 'Everglades',
            'parent_id' => $region->id,
            'primary_organization_id' => $organization->id,
        ], $result[0]['regions'][0]['units'][0]);
        $this->assertSame($organization->id, $result[0]['regions'][0]['primary_organization_id']);
        $this->assertSame($organization->id, $result[0]['regions'][0]['parent_id']);
    }
}
