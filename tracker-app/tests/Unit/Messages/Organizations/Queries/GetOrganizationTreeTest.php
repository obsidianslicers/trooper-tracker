<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Queries;

use App\Messages\Organizations\Queries\GetOrganizationTree;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetOrganizationTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_nested_hierarchy_structure(): void
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

        $result = (new GetOrganizationTree)->handle();

        $this->assertCount(2, $result);
        $this->assertSame('Alpha Organization', $result->first()['name']);
        $this->assertSame('AO', $result->first()['identifier_display']);
        $this->assertCount(1, $result->first()['regions']);
        $this->assertSame('Alpha Region', $result->first()['regions']->first()['name']);
        $this->assertCount(1, $result->first()['regions']->first()['units']);
        $this->assertSame('Alpha Unit', $result->first()['regions']->first()['units']->first()['name']);
    }

    public function test_handle_with_organization_id_filters_to_single_organization_tree(): void
    {
        $selected_organization = Organization::factory()
            ->asOrganization()
            ->withName('Selected Organization')
            ->create();

        Organization::factory()->asOrganization()->withName('Other Organization')->create();

        $result = (new GetOrganizationTree($selected_organization->id))->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Selected Organization', $result->first()['name']);
    }
}
