<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ProfileSubmitController.
 *
 * Verifies:
 * - Authenticated troopers can update their profile
 * - Profile fields are correctly updated
 * - Validation errors are displayed for invalid data
 * - Success flash message is shown
 * - Redirects to profile page after update
 * - Unauthenticated users are redirected to login
 */
class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_profile(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NAME => 'Old Name',
            Trooper::EMAIL => 'old@example.com',
            Trooper::PHONE => '5551234',
            Trooper::THEME => TrooperTheme::STORMTROOPER,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '555-5678',
                'theme' => TrooperTheme::SITH->value,
            ]);

        // Assert
        $trooper->refresh();
        $this->assertEquals('New Name', $trooper->name);
        $this->assertEquals('new@example.com', $trooper->email);
        $this->assertEquals('5555678', $trooper->phone); // Phone sanitized
        $this->assertEquals(TrooperTheme::SITH, $trooper->theme);
    }

    public function test_invoke_updates_name(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NAME => 'Original Name',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Updated Name',
                'email' => $trooper->email,
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $trooper->refresh();
        $this->assertEquals('Updated Name', $trooper->name);
    }

    public function test_invoke_updates_email(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'old@example.com',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => $trooper->name,
                'email' => 'updated@example.com',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $trooper->refresh();
        $this->assertEquals('updated@example.com', $trooper->email);
    }

    public function test_invoke_updates_phone(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::PHONE => '1111111',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => $trooper->name,
                'email' => $trooper->email,
                'phone' => '555-9999',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $trooper->refresh();
        $this->assertEquals('5559999', $trooper->phone);
    }

    public function test_invoke_updates_theme(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::THEME => TrooperTheme::STORMTROOPER,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => $trooper->name,
                'email' => $trooper->email,
                'theme' => TrooperTheme::REBEL->value,
            ]);

        // Assert
        $trooper->refresh();
        $this->assertEquals(TrooperTheme::REBEL, $trooper->theme);
    }

    public function test_invoke_sanitizes_phone_number(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => $trooper->name,
                'email' => $trooper->email,
                'phone' => '(555) 123-4567',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert - phone should be sanitized to digits only
        $trooper->refresh();
        $this->assertEquals('5551234567', $trooper->phone);
    }

    public function test_invoke_handles_null_phone(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::PHONE => '5551234',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => $trooper->name,
                'email' => $trooper->email,
                'phone' => null,
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertRedirect(route('account.profile'));
    }

    public function test_invoke_redirects_to_profile_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'email' => 'test@example.com',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertRedirect(route('account.profile'));
    }

    public function test_invoke_displays_success_flash_message(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'email' => 'test@example.com',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertSessionHas('flash_messages');
    }

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->post(route('account.profile'), [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'theme' => TrooperTheme::STORMTROOPER->value,
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_validates_name_is_required(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'email' => 'test@example.com',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertSessionHasErrors('name');
    }

    public function test_invoke_validates_email_is_required(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    public function test_invoke_validates_email_format(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'email' => 'invalid-email',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    public function test_invoke_validates_theme_is_required(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'email' => 'test@example.com',
            ]);

        // Assert
        $response->assertSessionHasErrors('theme');
    }

    public function test_invoke_validates_theme_is_valid_enum(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'email' => 'test@example.com',
                'theme' => 'invalid_theme',
            ]);

        // Assert
        $response->assertSessionHasErrors('theme');
    }

    public function test_invoke_validates_name_max_length(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => str_repeat('a', 257), // Exceeds 256 max
                'email' => 'test@example.com',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertSessionHasErrors('name');
    }

    public function test_invoke_validates_email_max_length(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'email' => str_repeat('a', 250) . '@example.com', // Exceeds 256 max
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    public function test_invoke_validates_phone_max_length(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'Test Name',
                'email' => 'test@example.com',
                'phone' => str_repeat('1', 17), // Exceeds 16 max
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert
        $response->assertSessionHasErrors('phone');
    }

    public function test_invoke_accepts_all_valid_theme_values(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        foreach (TrooperTheme::cases() as $theme)
        {
            // Act
            $response = $this->actingAs($trooper)
                ->post(route('account.profile'), [
                    'name' => 'Test Name',
                    'email' => 'test@example.com',
                    'theme' => $theme->value,
                ]);

            // Assert
            $response->assertRedirect(route('account.profile'));
            $trooper->refresh();
            $this->assertEquals($theme, $trooper->theme);
        }
    }

    public function test_invoke_persists_changes_to_database(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NAME => 'Old Name',
            Trooper::EMAIL => 'old@example.com',
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '555-1234',
                'theme' => TrooperTheme::SITH->value,
            ]);

        // Assert
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::ID => $trooper->id,
            Trooper::NAME => 'New Name',
            Trooper::EMAIL => 'new@example.com',
            Trooper::PHONE => '5551234',
            Trooper::THEME => TrooperTheme::SITH->value,
        ]);
    }

    public function test_invoke_only_updates_authenticated_trooper(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'trooper1@example.com',
        ]);
        $trooper2 = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'trooper2@example.com',
        ]);

        // Act
        $response = $this->actingAs($trooper1)
            ->post(route('account.profile'), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'theme' => TrooperTheme::STORMTROOPER->value,
            ]);

        // Assert - trooper1 should be updated
        $trooper1->refresh();
        $this->assertEquals('updated@example.com', $trooper1->email);

        // trooper2 should remain unchanged
        $trooper2->refresh();
        $this->assertEquals('trooper2@example.com', $trooper2->email);
    }

    public function test_invoke_updates_multiple_fields_atomically(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::NAME => 'Original Name',
            Trooper::EMAIL => 'original@example.com',
            Trooper::PHONE => null,
            Trooper::THEME => TrooperTheme::STORMTROOPER,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '555-9999',
                'theme' => TrooperTheme::REBEL->value,
            ]);

        // Assert - all fields should be updated
        $trooper->refresh();
        $this->assertEquals('New Name', $trooper->name);
        $this->assertEquals('new@example.com', $trooper->email);
        $this->assertEquals('5559999', $trooper->phone);
        $this->assertEquals(TrooperTheme::REBEL, $trooper->theme);
    }
}
