<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasNoticeScopesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2024, 1, 15));
    }

    public function test_scope_active(): void
    {
        // Arrange
        $active_notice = Notice::factory()->active()->create();
        $active_no_end = Notice::factory()->active()->create();
        Notice::factory()->create(['starts_at' => Carbon::now()->addDay()]); // Future
        Notice::factory()->create(['ends_at' => Carbon::now()->subDay()]); // Past

        // Act
        $result = Notice::active()->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($active_notice));
        $this->assertTrue($result->contains($active_no_end));
    }

    public function test_scope_past(): void
    {
        // Arrange
        $past_notice = Notice::factory()->past()->create();
        Notice::factory()->active()->create();
        Notice::factory()->future()->create();

        // Act
        $result = Notice::past()->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($past_notice));
    }

    public function test_scope_future(): void
    {
        // Arrange
        $future_notice = Notice::factory()->future()->create();
        Notice::factory()->active()->create();
        Notice::factory()->past()->create();

        // Act
        $result = Notice::future()->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($future_notice));
    }

    public function test_scope_visible_to(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;

        $trooper = Trooper::factory()->withAssignment($unit, member: true)->create();

        $global_notice = Notice::factory()->active()->create(['organization_id' => null]);
        $region_notice = Notice::factory()->active()->withOrganization($region)->create();
        $other_notice = Notice::factory()->active()->create(); // Belongs to another org

        // Act
        $result = Notice::visibleTo($trooper)->get();

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($global_notice));
        $this->assertTrue($result->contains($region_notice));
        $this->assertFalse($result->contains($other_notice));
    }

    public function test_scope_visible_to_with_unread_only(): void
    {
        // Arrange
        $global_notice_unread = Notice::factory()->active()->create(['organization_id' => null]);
        $global_notice_read = Notice::factory()->active()->create(['organization_id' => null]);
        $global_notice_marked_unread = Notice::factory()->active()->create(['organization_id' => null]);
        $trooper = Trooper::factory()->markAsRead($global_notice_read)->create();

        // Act
        $result = Notice::visibleTo($trooper, true)->get();

        // Assert
        $this->assertCount(2, $result, 'Should only find unread notices.');
        $this->assertTrue($result->contains($global_notice_unread));
        $this->assertTrue($result->contains($global_notice_marked_unread));
        $this->assertFalse($result->contains($global_notice_read));
    }

    public function test_scope_moderated_by_for_direct_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()->asModerator()->withAssignment($organization, moderator: true)->create();

        $moderated_notice = Notice::factory()->withOrganization($organization)->create();
        $other_notice = Notice::factory()->create();

        // Act
        $result = Notice::moderatedBy($moderator)->get();

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

        $unit_notice = Notice::factory()->withOrganization($unit)->create();
        $region_notice = Notice::factory()->withOrganization($region)->create();
        $other_notice = Notice::factory()->create();

        // Act
        $result = Notice::moderatedBy($moderator)->get();

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

        $org_notice = Notice::factory()->withOrganization($organization)->create();
        $region_notice = Notice::factory()->withOrganization($region)->create();

        // Act
        $result = Notice::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains($region_notice));
        $this->assertFalse($result->contains($org_notice));
    }

    public function test_scope_moderated_by_with_no_moderated_orgs_returns_nothing(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create(); // No assignments
        Notice::factory()->count(3)->create();

        // Act
        $result = Notice::moderatedBy($moderator)->get();

        // Assert
        $this->assertCount(0, $result);
    }
}