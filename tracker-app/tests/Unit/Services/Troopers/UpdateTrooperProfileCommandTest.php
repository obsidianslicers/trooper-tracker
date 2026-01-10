<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use App\Services\Troopers\UpdateTrooperProfileCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for UpdateTrooperProfileCommand.
 *
 * Verifies:
 * - Updates trooper's email address.
 * - Updates trooper's notification frequency.
 * - Sets setup_completed_at timestamp.
 * - Persists changes to database.
 */
class UpdateTrooperProfileCommandTest extends TestCase
{
    use RefreshDatabase;

    private UpdateTrooperProfileCommand $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateTrooperProfileCommand();
    }

    public function test_invoke_updates_email(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'old@example.com',
        ]);

        $data = [
            'email' => 'new@example.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals('new@example.com', $trooper->email);
    }

    public function test_invoke_updates_notification_frequency(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $data = [
            'email' => 'test@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals(NotificationFrequency::INSTANT, $trooper->notification_frequency);
    }

    public function test_invoke_sets_setup_completed_at_timestamp(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $data = [
            'email' => 'test@example.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertNotNull($trooper->setup_completed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $trooper->setup_completed_at);
    }

    public function test_invoke_updates_existing_setup_completed_at_timestamp(): void
    {
        // Arrange
        $originalTimestamp = now()->subDays(5);
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => $originalTimestamp,
        ]);

        $data = [
            'email' => 'test@example.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertNotNull($trooper->setup_completed_at);
        $this->assertTrue($trooper->setup_completed_at->isAfter($originalTimestamp));
    }

    public function test_invoke_persists_all_changes_to_database(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'old@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $data = [
            'email' => 'updated@example.com',
            'notification_frequency' => NotificationFrequency::INSTANT->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::ID => $trooper->id,
            Trooper::EMAIL => 'updated@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT->value,
        ]);

        // Verify setup_completed_at is not null
        $trooper->refresh();
        $this->assertNotNull($trooper->setup_completed_at);
    }
}
