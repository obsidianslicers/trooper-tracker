<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Notices\Queries;

use App\Features\Notices\Queries\GetTrooperNoticesQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperNoticesQuery.
 *
 * Verifies:
 * - Query construction with trooper
 * - Property access
 */
class GetTrooperNoticesQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetTrooperNoticesQuery($trooper);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
    }
}
