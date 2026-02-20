<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Costumes\Queries;

use App\Features\Costumes\Queries\GetCostumesPickerQuery;
use Tests\TestCase;

/**
 * Unit tests for GetCostumesPickerQuery.
 *
 * Verifies:
 * - Query construction with array of organization IDs
 * - Property access
 */
class GetCostumesPickerQueryTest extends TestCase
{
    public function test_construct_with_single_organization_id(): void
    {
        // Arrange
        $organization_ids = [123];

        // Act
        $subject = new GetCostumesPickerQuery($organization_ids);

        // Assert
        $this->assertSame($organization_ids, $subject->organization_ids);
    }

    public function test_construct_with_multiple_organization_ids(): void
    {
        // Arrange
        $organization_ids = [123, 456, 789];

        // Act
        $subject = new GetCostumesPickerQuery($organization_ids);

        // Assert
        $this->assertSame($organization_ids, $subject->organization_ids);
    }

    public function test_construct_with_empty_array(): void
    {
        // Arrange
        $organization_ids = [];

        // Act
        $subject = new GetCostumesPickerQuery($organization_ids);

        // Assert
        $this->assertSame($organization_ids, $subject->organization_ids);
    }
}
