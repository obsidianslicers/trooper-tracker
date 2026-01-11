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
 * - Updates trooper's name, email, phone, notification frequency, and theme.
 * - Only updates fields that exist in the data array (array_key_exists check).
 * - Sets setup_completed_at timestamp only when complete_setup is true.
 * - Does not modify fields not included in the data array.
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
            'notification_frequency' => NotificationFrequency::INSTANT->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals(NotificationFrequency::INSTANT, $trooper->notification_frequency);
    }

    public function test_invoke_sets_setup_completed_at_timestamp_when_complete_setup_is_true(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $data = [
            'email' => 'test@example.com',
        ];

        // Act
        ($this->subject)($trooper, $data, true);

        // Assert
        $trooper->refresh();
        $this->assertNotNull($trooper->setup_completed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $trooper->setup_completed_at);
    }

    public function test_invoke_updates_existing_setup_completed_at_timestamp_when_complete_setup_is_true(): void
    {
        // Arrange
        $originalTimestamp = now()->subDays(5);
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => $originalTimestamp,
        ]);

        $data = [
            'email' => 'test@example.com',
        ];

        // Act
        ($this->subject)($trooper, $data, true);

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
        ($this->subject)($trooper, $data, true);

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

    public function test_invoke_does_not_set_setup_completed_at_when_complete_setup_is_false(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $data = [
            'email' => 'test@example.com',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertNull($trooper->setup_completed_at);
    }

    public function test_invoke_updates_name(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::NAME => 'Old Name',
        ]);

        $data = [
            'name' => 'New Name',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals('New Name', $trooper->name);
    }

    public function test_invoke_updates_phone(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::PHONE => '555-1234',
        ]);

        $data = [
            'phone' => '555-5678',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals('555-5678', $trooper->phone);
    }

    public function test_invoke_updates_theme(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::THEME => \App\Enums\TrooperTheme::STORMTROOPER,
        ]);

        $data = [
            'theme' => \App\Enums\TrooperTheme::SITH->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals(\App\Enums\TrooperTheme::SITH, $trooper->theme);
    }

    public function test_invoke_only_updates_fields_present_in_data_array(): void
    {
        // Arrange
        $original_name = 'Original Name';
        $original_phone = '555-0000';
        $original_email = 'original@example.com';

        $trooper = Trooper::factory()->create([
            Trooper::NAME => $original_name,
            Trooper::PHONE => $original_phone,
            Trooper::EMAIL => $original_email,
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
        ]);

        $data = [
            'email' => 'updated@example.com',
            // name, phone, notification_frequency, theme not included
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals('updated@example.com', $trooper->email);
        $this->assertEquals($original_name, $trooper->name);
        $this->assertEquals($original_phone, $trooper->phone);
        $this->assertEquals(NotificationFrequency::DAILY, $trooper->notification_frequency);
    }

    public function test_invoke_does_not_update_name_when_not_in_data_array(): void
    {
        // Arrange
        $original_name = 'Original Name';
        $trooper = Trooper::factory()->create([
            Trooper::NAME => $original_name,
        ]);

        $data = [
            'email' => 'new@example.com',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals($original_name, $trooper->name);
    }

    public function test_invoke_does_not_update_email_when_not_in_data_array(): void
    {
        // Arrange
        $original_email = 'original@example.com';
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => $original_email,
        ]);

        $data = [
            'name' => 'New Name',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals($original_email, $trooper->email);
    }

    public function test_invoke_does_not_update_phone_when_not_in_data_array(): void
    {
        // Arrange
        $original_phone = '555-1234';
        $trooper = Trooper::factory()->create([
            Trooper::PHONE => $original_phone,
        ]);

        $data = [
            'email' => 'new@example.com',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals($original_phone, $trooper->phone);
    }

    public function test_invoke_does_not_update_notification_frequency_when_not_in_data_array(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT,
        ]);

        $data = [
            'email' => 'new@example.com',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals(NotificationFrequency::INSTANT, $trooper->notification_frequency);
    }

    public function test_invoke_does_not_update_theme_when_not_in_data_array(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::THEME => \App\Enums\TrooperTheme::REBEL,
        ]);

        $data = [
            'email' => 'new@example.com',
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals(\App\Enums\TrooperTheme::REBEL, $trooper->theme);
    }

    public function test_invoke_updates_all_fields_when_all_present_in_data_array(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::NAME => 'Old Name',
            Trooper::EMAIL => 'old@example.com',
            Trooper::PHONE => '555-0000',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::NEVER,
            Trooper::THEME => \App\Enums\TrooperTheme::STORMTROOPER,
        ]);

        $data = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '555-9999',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'theme' => \App\Enums\TrooperTheme::SITH->value,
        ];

        // Act
        ($this->subject)($trooper, $data);

        // Assert
        $trooper->refresh();
        $this->assertEquals('New Name', $trooper->name);
        $this->assertEquals('new@example.com', $trooper->email);
        $this->assertEquals('555-9999', $trooper->phone);
        $this->assertEquals(NotificationFrequency::DAILY, $trooper->notification_frequency);
        $this->assertEquals(\App\Enums\TrooperTheme::SITH, $trooper->theme);
    }

    public function test_invoke_handles_empty_data_array(): void
    {
        // Arrange
        $original_name = 'Original Name';
        $original_email = 'original@example.com';
        $original_phone = '555-1234';

        $trooper = Trooper::factory()->create([
            Trooper::NAME => $original_name,
            Trooper::EMAIL => $original_email,
            Trooper::PHONE => $original_phone,
        ]);

        $data = [];

        // Act
        ($this->subject)($trooper, $data);

        // Assert - nothing should change
        $trooper->refresh();
        $this->assertEquals($original_name, $trooper->name);
        $this->assertEquals($original_email, $trooper->email);
        $this->assertEquals($original_phone, $trooper->phone);
    }
}
