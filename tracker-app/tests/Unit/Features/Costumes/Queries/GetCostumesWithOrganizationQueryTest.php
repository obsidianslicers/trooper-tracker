<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Costumes\Queries;

use App\Features\Costumes\Queries\GetCostumesWithOrganizationQuery;
use Tests\TestCase;

/**
 * Unit tests for GetCostumesWithOrganizationQuery.
 *
 * Verifies:
 * - Query construction with organization ID
 * - Query construction without organization ID
 * - Property access
 */
class GetCostumesWithOrganizationQueryTest extends TestCase
{
    public function test_construct_with_organization_id(): void
    {
        // Arrange
        $organization_id = 123;

        // Act
        $subject = new GetCostumesWithOrganizationQuery($organization_id);

        // Assert
        $this->assertSame($organization_id, $subject->organization_id);
    }

    public function test_construct_without_organization_id(): void
    {
        // Act
        $subject = new GetCostumesWithOrganizationQuery();

        // Assert
        $this->assertNull($subject->organization_id);
    }
}
