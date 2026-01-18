<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Notices\Queries;

use App\Features\Notices\Queries\GetTrooperNoticeForDisplayQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetNoticesForDisplayQuery.
 *
 * Verifies:
 * - Query construction with trooper
 * - Query construction with unread_only flag
 * - Property access
 */
class GetTrooperNoticeForDisplayQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_trooper_only(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetTrooperNoticeForDisplayQuery($trooper);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertFalse($subject->unread_only);
    }

    public function test_construct_with_unread_only_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetTrooperNoticeForDisplayQuery($trooper, true);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertTrue($subject->unread_only);
    }

    public function test_construct_with_unread_only_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetTrooperNoticeForDisplayQuery($trooper, false);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertFalse($subject->unread_only);
    }
}
