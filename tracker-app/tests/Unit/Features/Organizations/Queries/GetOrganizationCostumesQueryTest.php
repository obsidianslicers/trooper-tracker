<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationCostumesQuery;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationCostumesQuery.
 *
 * Verifies:
 * - Query construction with no parameters
 * - Query construction with organization IDs
 * - Property access
 */
class GetOrganizationCostumesQueryTest extends TestCase
{
    public function test_construct_with_no_parameters(): void
    {
        // Act
        $subject = new GetOrganizationCostumesQuery();

        // Assert
        $this->assertNull($subject->organization_ids);
    }

    public function test_construct_with_organization_ids(): void
    {
        // Arrange
        $ids = [1, 2, 3];

        // Act
        $subject = new GetOrganizationCostumesQuery($ids);

        // Assert
        $this->assertSame($ids, $subject->organization_ids);
    }
}
