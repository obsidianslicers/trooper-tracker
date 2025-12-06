<?php

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_as_authenticated_user_returns_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('account.profile'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.account.profile');
        $response->assertViewHas('trooper', function ($view_trooper) use ($trooper)
        {
            return $view_trooper->id === $trooper->id;
        });
    }

    public function test_invoke_as_guest_redirects_to_login(): void
    {
        // Arrange
        // No user is authenticated.

        // Act
        $response = $this->get(route('account.profile'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}