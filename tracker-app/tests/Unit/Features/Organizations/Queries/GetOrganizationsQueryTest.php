<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationsQuery;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationsQuery.
 *
 * Verifies:
 * - Query construction (no parameters)
 */
class GetOrganizationsQueryTest extends TestCase
{
    public function test_construct_with_no_parameters(): void
    {
        // Act
        $subject = new GetOrganizationsQuery();

        // Assert
        $this->assertInstanceOf(GetOrganizationsQuery::class, $subject);
    }
}
