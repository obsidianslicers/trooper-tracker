<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\NotificationFrequency;
use App\Http\Controllers\Account\SetupSubmitController;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SetupSubmitController.
 *
 * Verifies:
 * - Authenticated trooper can submit setup form.
 * - Updates trooper profile (email, notification frequency, setup_completed_at).
 * - Creates organization assignments.
 * - Redirects to account.costumes route on success.
 * - Shows flash message on successful update.
 * - Validates required fields.
 * - Requires authentication.
 */
class SetupSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_profile(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'old@example.com',
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY,
            Trooper::SETUP_COMPLETED_AT => null,
        ]);

        $data = [
            'email' => 'new@gmail.com',
            'notification_frequency' => NotificationFrequency::INSTANT->value,
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));

        $trooper->refresh();
        $this->assertEquals('new@gmail.com', $trooper->email);
        $this->assertEquals(NotificationFrequency::INSTANT, $trooper->notification_frequency);
        $this->assertNotNull($trooper->setup_completed_at);
    }

    public function test_invoke_creates_organization_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $data = [
            'email' => 'test@gmail.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [
                $organization->id => [
                    'assignment' => $organization->id,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));

        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_handles_multiple_organization_assignments(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $data = [
            'email' => 'test@gmail.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [
                $org1->id => [
                    'assignment' => $org1->id,
                ],
                $org2->id => [
                    'assignment' => $org2->id,
                ],
            ],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));

        $this->assertEquals(2, $trooper->trooper_assignments()->count());
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org1->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
        $this->assertDatabaseHas(TrooperAssignment::class, [
            TrooperAssignment::TROOPER_ID => $trooper->id,
            TrooperAssignment::ORGANIZATION_ID => $org2->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
    }

    public function test_invoke_shows_flash_message_on_success(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $data = [
            'email' => 'test@gmail.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertSessionHas('flash_messages');
    }

    public function test_invoke_requires_email(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $data = [
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    public function test_invoke_requires_notification_frequency(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $data = [
            'email' => 'test@gmail.com',
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors('notification_frequency');
    }

    public function test_invoke_validates_email_format(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $data = [
            'email' => 'invalid-email',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    public function test_invoke_validates_email_uniqueness(): void
    {
        // Arrange
        $existingTrooper = Trooper::factory()->create([
            Trooper::EMAIL => 'existing@example.com',
        ]);

        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'current@example.com',
        ]);

        $data = [
            'email' => 'existing@example.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    public function test_invoke_allows_trooper_to_keep_same_email(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            Trooper::EMAIL => 'current@gmail.com',
        ]);

        $data = [
            'email' => 'current@gmail.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertRedirect(route('account.costumes'));
        $response->assertSessionHasNoErrors();
    }

    public function test_invoke_validates_notification_frequency_values(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        $data = [
            'email' => 'test@gmail.com',
            'notification_frequency' => 'invalid-frequency',
            'organizations' => [],
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors('notification_frequency');
    }

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $data = [
            'email' => 'test@gmail.com',
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [],
        ];

        // Act
        $response = $this->post(action(SetupSubmitController::class), $data);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
