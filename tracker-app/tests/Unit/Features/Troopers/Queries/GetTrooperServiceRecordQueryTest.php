<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperServiceRecordQuery.
 *
 * Verifies:
 * - Query construction with trooper_id
 * - Property access
 */
class GetTrooperServiceRecordQueryTest extends TestCase
{
    public function test_construct_with_trooper_id(): void
    {
        // Arrange
        $trooper_id = 42;

        // Act
        $subject = new GetTrooperServiceRecordQuery($trooper_id);

        // Assert
        $this->assertSame($trooper_id, $subject->trooper_id);
    }
}
