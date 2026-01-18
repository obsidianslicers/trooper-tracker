<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperNotificationsQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperNotificationsQuery.
 *
 * Verifies:
 * - Query construction with trooper
 * - Property access
 */
class GetTrooperNotificationsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetTrooperNotificationsQuery($trooper);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
    }
}
