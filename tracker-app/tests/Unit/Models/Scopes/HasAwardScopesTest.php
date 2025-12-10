<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasAwardScopesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2024, 1, 15));
    }

    public function test_scope_moderated_by_for_direct_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->withAssignment($organization, moderator: true)->create();

        $moderated_notice = Award::factory()->withOrganization($organization)->create();
        $other_notice = Award::factory()->create();

        // Act
        $result = Award::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($moderated_notice));
        $this->assertFalse($result->contains($other_notice));
    }

    public function test_scope_moderated_by_for_child_organization(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $moderator = Trooper::factory()->asModerator()->withAssignment($region, moderator: true)->create();

        $unit_notice = Award::factory()->withOrganization($unit)->create();
        $region_notice = Award::factory()->withOrganization($region)->create();
        $other_notice = Award::factory()->create();

        // Act
        $result = Award::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($unit_notice));
        $this->assertTrue($result->contains($region_notice));
        $this->assertFalse($result->contains($other_notice));
    }

    public function test_scope_moderated_by_does_not_include_parent_organization_notices(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $moderator = Trooper::factory()->asModerator()->withAssignment($region, moderator: true)->create();

        $org_notice = Award::factory()->withOrganization($organization)->create();
        $region_notice = Award::factory()->withOrganization($region)->create();

        // Act
        $result = Award::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($region_notice));
        $this->assertFalse($result->contains($org_notice));
    }

    public function test_scope_moderated_by_with_no_moderated_orgs_returns_nothing(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create(); // No assignments
        Award::factory()->count(3)->create();

        // Act
        $result = Award::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(0, $result);
    }
}