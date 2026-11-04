<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Resources;

use App\Enums\OrganizationType;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Messages\Organizations\Resources\OrganizationOptions;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrganizationOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_lists_units_under_regions_by_default(): void
    {
        $this->createHierarchy();

        $subject = new OrganizationOptions(GetOrganizationHierarchy::call());

        $this->assertSame(
            ['501st', ' — Florida', ' — — Everglades'],
            array_column($subject->toArray(new Request), 'label')
        );
    }

    public function test_to_array_stops_at_regions_for_region_type(): void
    {
        $this->createHierarchy();

        $subject = new OrganizationOptions(GetOrganizationHierarchy::call(), OrganizationType::REGION);

        $this->assertSame(
            ['501st', ' — Florida'],
            array_column($subject->toArray(new Request), 'label')
        );
    }

    public function test_to_array_lists_only_organizations_for_organization_type(): void
    {
        $organization = $this->createHierarchy();

        $subject = new OrganizationOptions(
            GetOrganizationHierarchy::call(),
            OrganizationType::ORGANIZATION
        );

        $this->assertSame(
            [['value' => $organization->id, 'label' => '501st']],
            $subject->toArray(new Request)
        );
    }

    private function createHierarchy(): Organization
    {
        $organization = Organization::factory()->asOrganization()->withName('501st')->create();
        $region = Organization::factory()->asRegion()->withParent($organization)
            ->withName('Florida')->create();
        Organization::factory()->asUnit()->withParent($region)->withName('Everglades')->create();

        return $organization;
    }
}
