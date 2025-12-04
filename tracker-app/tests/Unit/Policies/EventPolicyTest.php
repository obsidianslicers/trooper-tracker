<?php

namespace Tests\Unit\Policies;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Policies\EventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    private EventPolicy $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new EventPolicy();
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
        $event = Event::factory()->make();

        // Act
        $result = $this->subject->update($trooper, $event);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_as_moderator_on_moderated_event_returns_true(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $trooper = Trooper::factory()->asModerator()->withAssignment($unit, moderator: true)->create();
        $event = Event::factory()->for($unit)->create();

        // Act
        $result = $this->subject->update($trooper, $event);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_as_moderator_on_unmoderated_event_returns_false(): void
    {
        // Arrange
        $moderated_unit = Organization::factory()->unit()->create();
        $unmoderated_unit = Organization::factory()->unit()->create();
        $trooper = Trooper::factory()->asModerator()->withAssignment($moderated_unit, moderator: true)->create();
        $event = Event::factory()->for($unmoderated_unit)->create();

        // Act
        $result = $this->subject->update($trooper, $event);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_restore_and_force_delete_always_return_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdmin()->make();
        $event = Event::factory()->make();

        // Act
        $delete_result = $this->subject->delete($trooper, $event);
        $restore_result = $this->subject->restore($trooper, $event);
        $force_delete_result = $this->subject->forceDelete($trooper, $event);

        // Assert
        $this->assertFalse($delete_result, 'Delete should return false.');
        $this->assertFalse($restore_result, 'Restore should return false.');
        $this->assertFalse($force_delete_result, 'Force delete should return false.');
    }
}
