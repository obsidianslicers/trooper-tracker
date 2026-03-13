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
}