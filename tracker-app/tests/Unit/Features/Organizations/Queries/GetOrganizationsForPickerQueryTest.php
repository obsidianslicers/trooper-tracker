<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationsForPickerQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetOrganizationsForPickerQuery.
 *
 * Verifies:
 * - Query construction with trooper and data
 * - Default parameter values
 * - Property access
 */
class GetOrganizationsForPickerQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_default_values(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetOrganizationsForPickerQuery($trooper, []);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertFalse($subject->moderated_only);
        $this->assertNull($subject->organization_id);
    }

    public function test_construct_with_moderated_only_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetOrganizationsForPickerQuery($trooper, ['moderated_only' => true]);

        // Assert
        $this->assertTrue($subject->moderated_only);
    }

    public function test_construct_with_organization_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $subject = new GetOrganizationsForPickerQuery($trooper, ['organization_id' => 42]);

        // Assert
        $this->assertEquals(42, $subject->organization_id);
    }
}
