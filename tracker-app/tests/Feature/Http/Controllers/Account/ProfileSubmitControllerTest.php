<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\TrooperTheme;
use App\Http\Controllers\Account\ProfileSubmitController;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ProfileSubmitController.
 *
 * Verifies:
 * - Authenticated troopers can update their profile
 * - Phone numbers are normalized before saving
 * - Redirects to account.profile after update
 * - Success flash message is set
 * - Validation errors are returned for invalid input
 * - Unauthenticated users are redirected to login
 */
class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_profile(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::LEGAL_NAME => 'Original Name',
            Trooper::DISPLAY_NAME => 'Original Display',
            Trooper::EMAIL => 'original@example.com',
            Trooper::PHONE => '5550000000',
            Trooper::THEME => TrooperTheme::STORMTROOPER,
        ]);

        $data = [
            Trooper::LEGAL_NAME => 'Updated Name',
            Trooper::DISPLAY_NAME => 'Updated Display',
            Trooper::EMAIL => 'updated@example.com',
            Trooper::PHONE => '(555) 111-2222',
            Trooper::THEME => TrooperTheme::REBEL->value,
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(ProfileSubmitController::class), $data);

        // Assert
        $response->assertRedirect(route('account.profile'));

        $trooper->refresh();
        $this->assertSame('Updated Name', $trooper->legal_name);
        $this->assertSame('Updated Display', $trooper->display_name);
        $this->assertSame('updated@example.com', $trooper->email);
        $this->assertSame('5551112222', $trooper->phone);
        $this->assertEquals(TrooperTheme::REBEL, $trooper->theme);
    }

    public function test_invoke_sets_success_flash_message(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $data = [
            Trooper::LEGAL_NAME => 'Updated Name',
            Trooper::DISPLAY_NAME => 'Updated Display',
            Trooper::EMAIL => 'updated@example.com',
            Trooper::PHONE => '5551112222',
            Trooper::THEME => TrooperTheme::CLONE ->value,
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(ProfileSubmitController::class), $data);

        // Assert
        $response->assertSessionHas('flash_messages');
    }

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $data = [
            Trooper::LEGAL_NAME => 'Updated Name',
            Trooper::DISPLAY_NAME => 'Updated Display',
            Trooper::EMAIL => 'updated@example.com',
            Trooper::PHONE => '5551112222',
            Trooper::THEME => TrooperTheme::SITH->value,
        ];

        // Act
        $response = $this->post(action(ProfileSubmitController::class), $data);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_validates_required_fields(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $data = [
            Trooper::LEGAL_NAME => '',
            Trooper::DISPLAY_NAME => '',
            Trooper::EMAIL => '',
            Trooper::THEME => '',
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(ProfileSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors([
            Trooper::LEGAL_NAME,
            Trooper::DISPLAY_NAME,
            Trooper::EMAIL,
            Trooper::THEME,
        ]);
    }

    public function test_invoke_validates_email_format(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $data = [
            Trooper::LEGAL_NAME => 'Updated Name',
            Trooper::DISPLAY_NAME => 'Updated Display',
            Trooper::EMAIL => 'not-an-email',
            Trooper::THEME => TrooperTheme::BOUNTY_HUNTER->value,
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(ProfileSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors([Trooper::EMAIL]);
    }

    public function test_invoke_validates_theme_value(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $data = [
            Trooper::LEGAL_NAME => 'Updated Name',
            Trooper::DISPLAY_NAME => 'Updated Display',
            Trooper::EMAIL => 'updated@example.com',
            Trooper::THEME => 'invalid-theme',
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(action(ProfileSubmitController::class), $data);

        // Assert
        $response->assertSessionHasErrors([Trooper::THEME]);
    }
}
