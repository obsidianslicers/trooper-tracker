<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Costume;
use App\Models\Trooper;
use App\Policies\CostumePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CostumePolicy.
 *
 * Verifies:
 * - Only administrators can create costumes
 * - Only administrators can update costumes
 * - No one can delete, restore, or force delete costumes
 */
class CostumePolicyTest extends TestCase
{
    use RefreshDatabase;

    private CostumePolicy $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CostumePolicy();
    }

    public function test_create_as_administrator_returns_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_create_as_moderator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asModerator()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_as_active_member_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_as_pending_trooper_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_as_retired_trooper_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_as_administrator_returns_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->update($trooper, $costume);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_as_moderator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asModerator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->update($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_as_active_member_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->update($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_as_pending_trooper_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->update($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_as_retired_trooper_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->update($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_as_administrator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->delete($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_as_moderator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asModerator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->delete($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_as_member_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->delete($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_restore_as_administrator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->restore($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_restore_as_moderator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asModerator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->restore($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_restore_as_member_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->restore($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_force_delete_as_administrator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->forceDelete($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_force_delete_as_moderator_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asModerator()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->forceDelete($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_force_delete_as_member_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->make();
        $costume = Costume::factory()->make();

        // Act
        $result = $this->subject->forceDelete($trooper, $costume);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_restore_and_force_delete_always_return_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->make();
        $costume = Costume::factory()->make();

        // Act
        $delete_result = $this->subject->delete($trooper, $costume);
        $restore_result = $this->subject->restore($trooper, $costume);
        $force_delete_result = $this->subject->forceDelete($trooper, $costume);

        // Assert
        $this->assertFalse($delete_result, 'Delete should return false.');
        $this->assertFalse($restore_result, 'Restore should return false.');
        $this->assertFalse($force_delete_result, 'Force delete should return false.');
    }
}
