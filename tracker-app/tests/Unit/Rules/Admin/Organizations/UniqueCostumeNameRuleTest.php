<?php

declare(strict_types=1);

namespace Tests\Unit\Rules\Admin\Organizations;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Rules\Admin\Organizations\UniqueCostumeNameRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Rules\Admin\Organizations\UniqueCostumeNameRule
 */
class UniqueCostumeNameRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_passes_when_costume_name_is_unique_within_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stormtrooper',
        ]);

        $subject = new UniqueCostumeNameRule($organization);
        $failed = false;

        // Act
        $subject->validate('name', 'Darth Vader', function () use (&$failed)
        {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed);
    }

    public function test_validate_fails_when_costume_name_already_exists_in_organization(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stormtrooper',
        ]);

        $subject = new UniqueCostumeNameRule($organization);
        $failed = false;

        // Act
        $subject->validate('name', 'Stormtrooper', function () use (&$failed)
        {
            $failed = true;
        });

        // Assert
        $this->assertTrue($failed);
    }

    public function test_validate_passes_when_costume_name_exists_in_different_organization(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        OrganizationCostume::factory()->create([
            'organization_id' => $organization1->id,
            'name' => 'Stormtrooper',
        ]);

        $subject = new UniqueCostumeNameRule($organization2);
        $failed = false;

        // Act
        $subject->validate('name', 'Stormtrooper', function () use (&$failed)
        {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed);
    }

    public function test_validate_passes_when_updating_costume_with_same_name(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $costume = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stormtrooper',
        ]);

        $subject = new UniqueCostumeNameRule($organization);
        $failed = false;

        // Act
        $subject->validate("costumes.{$costume->id}.name", 'Stormtrooper', function () use (&$failed)
        {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed);
    }

    public function test_validate_fails_when_updating_costume_to_name_used_by_another_costume(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $costume1 = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stormtrooper',
        ]);
        $costume2 = OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Darth Vader',
        ]);

        $subject = new UniqueCostumeNameRule($organization);
        $failed = false;

        // Act - Try to rename costume2 to 'Stormtrooper' (already used by costume1)
        $subject->validate("costumes.{$costume2->id}.name", 'Stormtrooper', function () use (&$failed)
        {
            $failed = true;
        });

        // Assert
        $this->assertTrue($failed);
    }

    public function test_validate_is_case_sensitive(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        OrganizationCostume::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Stormtrooper',
        ]);

        $subject = new UniqueCostumeNameRule($organization);
        $failed = false;

        // Act
        $subject->validate('name', 'stormtrooper', function () use (&$failed)
        {
            $failed = true;
        });

        // Assert
        $this->assertFalse($failed, 'Validation should pass because check is case-sensitive');
    }
}
