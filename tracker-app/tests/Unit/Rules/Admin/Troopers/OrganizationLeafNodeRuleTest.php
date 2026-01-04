<?php

declare(strict_types=1);

namespace Tests\Unit\Rules\Admin\Troopers;

use App\Models\Organization;
use App\Rules\Admin\Troopers\OrganizationLeafNodeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationLeafNodeRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_passes_for_null_value(): void
    {
        // Arrange
        $parent_organization = Organization::factory()->create();
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };
        $subject = new OrganizationLeafNodeRule($parent_organization);

        // Act
        $subject->validate('assignment', null, $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed for null value.');
    }

    public function test_validate_passes_for_non_existent_organization(): void
    {
        // Arrange
        $parent_organization = Organization::factory()->create();
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };
        $subject = new OrganizationLeafNodeRule($parent_organization);

        // Act
        $subject->validate('assignment', 99999, $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed for non-existent organization.');
    }

    public function test_validate_passes_for_valid_leaf_node_descendant(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $parent_organization = $region->parent;

        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };
        $subject = new OrganizationLeafNodeRule($parent_organization);

        // Act
        $subject->validate('assignment', $unit->id, $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed for valid leaf node descendant.');
    }

    public function test_validate_fails_when_organization_has_children(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $parent_organization = $region->parent;

        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };
        $subject = new OrganizationLeafNodeRule($parent_organization);

        // Act - Try to assign the region (which has child units)
        $subject->validate('assignment', $region->id, $fail);

        // Assert
        $this->assertTrue($fail_was_called, 'The validation rule should have failed when organization has children.');
    }

    public function test_validate_fails_when_organization_not_descendant(): void
    {
        // Arrange
        $parent_organization = Organization::factory()->create();
        $unrelated_unit = Organization::factory()->unit()->create();

        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };
        $subject = new OrganizationLeafNodeRule($parent_organization);

        // Act - Try to assign an organization from a different hierarchy
        $subject->validate('assignment', $unrelated_unit->id, $fail);

        // Assert
        $this->assertTrue($fail_was_called, 'The validation rule should have failed when organization is not a descendant.');
    }

    public function test_validate_passes_for_direct_child_leaf_node(): void
    {
        // Arrange
        $region = Organization::factory()->region()->create();
        $parent_organization = $region->parent;

        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };
        $subject = new OrganizationLeafNodeRule($parent_organization);

        // Act - Region is a direct child and has no children (leaf node)
        $subject->validate('assignment', $region->id, $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed for direct child leaf node.');
    }

    public function test_validate_fails_when_direct_child_has_children(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $parent_organization = $region->parent;

        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };
        $subject = new OrganizationLeafNodeRule($parent_organization);

        // Act - Try to assign parent organization itself
        $subject->validate('assignment', $parent_organization->id, $fail);

        // Assert
        $this->assertTrue($fail_was_called, 'The validation rule should have failed when direct child has children.');
    }
}
