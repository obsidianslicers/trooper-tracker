<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationHierarchyQuery;
use App\Features\Organizations\Queries\GetOrganizationHierarchyQueryHandler;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetOrganizationHierarchyQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_nested_hierarchy_structure(): void
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('Alpha Organization')
            ->withIdentifierDisplay('AO')
            ->create();

        $region = Organization::factory()
            ->asRegion()
            ->withParent($organization)
            ->withName('Alpha Region')
            ->create();

        Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withName('Alpha Unit')
            ->create();

        Organization::factory()->asOrganization()->withName('Zulu Organization')->create();

        $subject = new GetOrganizationHierarchyQueryHandler();

        $result = $subject(new GetOrganizationHierarchyQuery());

        $this->assertCount(2, $result);
        $this->assertSame('Alpha Organization', $result->first()['name']);
        $this->assertSame('AO', $result->first()['identifier_display']);
        $this->assertCount(1, $result->first()['regions']);
        $this->assertSame('Alpha Region', $result->first()['regions']->first()['name']);
        $this->assertCount(1, $result->first()['regions']->first()['units']);
        $this->assertSame('Alpha Unit', $result->first()['regions']->first()['units']->first()['name']);
    }

    public function test_invoke_with_organization_id_filters_to_single_organization_tree(): void
    {
        $selected_organization = Organization::factory()
            ->asOrganization()
            ->withName('Selected Organization')
            ->create();

        Organization::factory()->asOrganization()->withName('Other Organization')->create();

        $subject = new GetOrganizationHierarchyQueryHandler();

        $result = $subject(new GetOrganizationHierarchyQuery($selected_organization->id));

        $this->assertCount(1, $result);
        $this->assertSame('Selected Organization', $result->first()['name']);
    }
}
