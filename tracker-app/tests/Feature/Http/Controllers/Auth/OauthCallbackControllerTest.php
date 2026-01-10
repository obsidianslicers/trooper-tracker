<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for the OauthCallbackController.
 *
 * Validates OAuth callback handling for various scenarios including
 * existing accounts, new registrations, and inactive troopers.
 */
class OauthCallbackControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_logs_in_trooper_with_existing_oauth_account(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $oauth_login = OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => 'google',
            OauthLogin::PROVIDER_ID => 'google-123',
        ]);

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-123');

        Socialite::shouldReceive('driver->user')->andReturn($socialite_user);

        // Act
        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        // Assert
        $response->assertRedirect('/');
        $this->assertTrue(Auth::check());
        $this->assertEquals($trooper->id, Auth::id());
    }

    public function test_invoke_redirects_inactive_trooper_with_oauth_account(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->create();
        $oauth_login = OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => 'google',
            OauthLogin::PROVIDER_ID => 'google-123',
        ]);

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-123');

        Socialite::shouldReceive('driver->user')->andReturn($socialite_user);

        // Act
        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        // Assert
        $response->assertRedirect(route('auth.inactive'));
        $this->assertFalse(Auth::check());
    }

    public function test_invoke_redirects_to_registration_for_new_trooper(): void
    {
        // Arrange
        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-456');
        $socialite_user->shouldReceive('getEmail')->andReturn('newuser@example.com');
        $socialite_user->shouldReceive('getName')->andReturn('New User');
        $socialite_user->token = 'mock-token';
        $socialite_user->refreshToken = 'mock-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($socialite_user);

        // Act
        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        // Assert
        $response->assertRedirect(route('auth.register'));
        $response->assertSessionHas('registration_auth');
        $response->assertSessionHas('oauth_pending');
        $this->assertFalse(Auth::check());
    }

    public function test_invoke_links_oauth_to_existing_trooper_and_logs_in(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::EMAIL => 'existing@example.com',
        ]);

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-789');
        $socialite_user->shouldReceive('getEmail')->andReturn('existing@example.com');
        $socialite_user->token = 'mock-token';
        $socialite_user->refreshToken = 'mock-refresh-token';

        Socialite::shouldReceive('driver->user')->andReturn($socialite_user);

        // Act
        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        // Assert
        $response->assertRedirect('/');
        $this->assertTrue(Auth::check());
        $this->assertEquals($trooper->id, Auth::id());
        $this->assertDatabaseHas(OauthLogin::class, [
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => 'google',
            OauthLogin::PROVIDER_ID => 'google-789',
        ]);
    }

    public function test_invoke_redirects_inactive_existing_trooper_to_inactive_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create([
            Trooper::EMAIL => 'pending@example.com',
        ]);

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-999');
        $socialite_user->shouldReceive('getEmail')->andReturn('pending@example.com');

        Socialite::shouldReceive('driver->user')->andReturn($socialite_user);

        // Act
        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        // Assert
        $response->assertRedirect(route('auth.inactive'));
        $this->assertFalse(Auth::check());
    }
}
