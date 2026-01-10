<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for the OauthRedirectController.
 *
 * Validates that OAuth provider redirects are initiated correctly.
 */
class OauthRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_redirects_to_google_oauth_provider(): void
    {
        // Arrange
        $redirect_url = 'https://accounts.google.com/o/oauth2/auth?...';

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturnSelf();

        Socialite::shouldReceive('redirect')
            ->once()
            ->andReturn(redirect($redirect_url));

        // Act
        $response = $this->get(route('auth.oauth-redirect', ['provider' => 'google']));

        // Assert
        $response->assertRedirect($redirect_url);
    }

    public function test_invoke_redirects_to_xenforo_oauth_provider(): void
    {
        // Arrange
        $redirect_url = 'https://forum.example.com/oauth/authorize?...';

        Socialite::shouldReceive('driver')
            ->once()
            ->with('xenforo')
            ->andReturnSelf();

        Socialite::shouldReceive('redirect')
            ->once()
            ->andReturn(redirect($redirect_url));

        // Act
        $response = $this->get(route('auth.oauth-redirect', ['provider' => 'xenforo']));

        // Assert
        $response->assertRedirect($redirect_url);
    }
}
