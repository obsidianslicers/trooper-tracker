<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasOrganizationScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_of_type_organization_scope_filters_by_type(): void
    {
        $query = Organization::query()->ofTypeOrganizations();

        $this->assertStringContainsString('"type" = ?', $query->toBase()->toSql());
        $this->assertSame([OrganizationType::ORGANIZATION->value], $query->getBindings());
    }

    public function test_of_type_regions_scope_filters_by_type(): void
    {
        $query = Organization::query()->ofTypeRegions();

        $this->assertStringContainsString('"type" = ?', $query->toBase()->toSql());
        $this->assertSame([OrganizationType::REGION->value], $query->getBindings());
    }

    public function test_of_type_units_scope_filters_by_type(): void
    {
        $query = Organization::query()->ofTypeUnits();

        $this->assertStringContainsString('"type" = ?', $query->toBase()->toSql());
        $this->assertSame([OrganizationType::UNIT->value], $query->getBindings());
    }

    public function test_fully_loaded_orders_by_name_and_filters_to_organizations(): void
    {
        $query = Organization::query()->fullyLoaded();

        $this->assertStringContainsString('"type" = ?', $query->toBase()->toSql());
        $this->assertStringContainsString('order by "name" asc', $query->toBase()->toSql());
    }

    public function test_moderated_by_returns_unmodified_query_for_administrator(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $base_sql = Organization::query()->toBase()->toSql();
        $moderated_sql = Organization::query()->moderatedBy($trooper)->toBase()->toSql();

        $this->assertSame($base_sql, $moderated_sql);
    }

    public function test_within_node_path_returns_organization_and_descendants(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();
        Organization::factory()->asOrganization()->create();

        $club_ids = Organization::withinNodePath($club->fresh()->node_path)
            ->pluck(Organization::ID)->all();
        $region_ids = Organization::withinNodePath($region->fresh()->node_path)
            ->pluck(Organization::ID)->all();

        $this->assertEqualsCanonicalizing([$club->id, $region->id, $unit->id], $club_ids);
        $this->assertEqualsCanonicalizing([$region->id, $unit->id], $region_ids);
    }

    public function test_on_branch_of_node_path_returns_ancestors_self_and_descendants(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();
        Organization::factory()->asRegion()->withParent($club)->create();
        Organization::factory()->asOrganization()->create();

        $ids = Organization::onBranchOfNodePath($region->fresh()->node_path)
            ->pluck(Organization::ID)->all();

        $this->assertEqualsCanonicalizing([$club->id, $region->id, $unit->id], $ids);
    }

    public function test_on_branch_of_node_path_keeps_preceding_constraints(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        $other_club = Organization::factory()->asOrganization()->create();

        // An ungrouped OR would let $club/$region through despite the preceding id constraint.
        $ids = Organization::where(Organization::ID, $other_club->id)
            ->onBranchOfNodePath($region->fresh()->node_path)
            ->pluck(Organization::ID)
            ->all();

        $this->assertSame([], $ids);
    }
}
