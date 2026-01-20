<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationHierarchyQuery;
use Tests\TestCase;

class GetOrganizationHierarchyQueryTest extends TestCase
{
    public function test_construct_with_no_organization_id(): void
    {
        // Act
        $subject = new GetOrganizationHierarchyQuery(null);

        // Assert
        $this->assertNull($subject->organization_id);
    }

    public function test_construct_with_organization_id(): void
    {
        // Arrange
        $organization_id = 123;

        // Act
        $subject = new GetOrganizationHierarchyQuery($organization_id);

        // Assert
        $this->assertSame($organization_id, $subject->organization_id);
    }
}
