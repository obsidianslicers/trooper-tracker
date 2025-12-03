<?php

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\TrooperTheme;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_profile_and_redirects(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create([
            'name' => 'Old Name',
            'theme' => TrooperTheme::STORMTROOPER,
        ]);

        $update_data = [
            'name' => 'New Name',
            'email' => 'new.email@example.com',
            'phone' => '1234567890',
            'theme' => TrooperTheme::SITH->value,
        ];

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.profile'), $update_data);

        // Assert
        $response->assertRedirect(route('account.profile'));

        $this->assertDatabaseHas(Trooper::class, [
            'id' => $trooper->id,
            'name' => 'New Name',
            'email' => 'new.email@example.com',
            'theme' => TrooperTheme::SITH->value,
        ]);
    }

    public function test_invoke_as_guest_redirects_to_login(): void
    {
        // Arrange
        // No user is authenticated

        // Act
        $response = $this->post(route('account.profile'), []);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}