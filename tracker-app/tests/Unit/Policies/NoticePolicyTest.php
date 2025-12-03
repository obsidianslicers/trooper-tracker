<?php

namespace Tests\Unit\Policies;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Policies\NoticePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticePolicyTest extends TestCase
{
    use RefreshDatabase;

    private NoticePolicy $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new NoticePolicy();
    }

    public function test_create_as_administrator_returns_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdmin()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_create_as_moderator_returns_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asModerator()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertTrue($result);
    }

    public function test_create_as_member_returns_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asMember()->make();

        // Act
        $result = $this->subject->create($trooper);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_as_administrator_returns_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdmin()->make();
        $notice = Notice::factory()->make();

        // Act
        $result = $this->subject->update($trooper, $notice);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_as_moderator_on_moderated_notice_returns_true(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $trooper = Trooper::factory()->asModerator()->withAssignment($unit, moderator: true)->create();
        $notice = Notice::factory()->for($unit)->create();

        // Act
        $result = $this->subject->update($trooper, $notice);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_as_moderator_on_unmoderated_notice_returns_false(): void
    {
        // Arrange
        $moderated_unit = Organization::factory()->unit()->create();
        $unmoderated_unit = Organization::factory()->unit()->create();
        $trooper = Trooper::factory()->asModerator()->withAssignment($moderated_unit, moderator: true)->create();
        $notice = Notice::factory()->for($unmoderated_unit)->create();

        // Act
        $result = $this->subject->update($trooper, $notice);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_restore_and_force_delete_always_return_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdmin()->make();
        $notice = Notice::factory()->make();

        // Act
        $delete_result = $this->subject->delete($trooper, $notice);
        $restore_result = $this->subject->restore($trooper, $notice);
        $force_delete_result = $this->subject->forceDelete($trooper, $notice);

        // Assert
        $this->assertFalse($delete_result, 'Delete should return false.');
        $this->assertFalse($restore_result, 'Restore should return false.');
        $this->assertFalse($force_delete_result, 'Force delete should return false.');
    }
}
