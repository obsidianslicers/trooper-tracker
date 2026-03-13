<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class OauthRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_redirects_to_google_oauth_provider(): void
    {
        $response = $this->get(route('auth.oauth-redirect', ['provider' => 'google']));

        $response->assertRedirect();
    }

    public function test_invoke_redirects_to_xenforo_oauth_provider(): void
    {
        $response = $this->get(route('auth.oauth-redirect', ['provider' => 'xenforo']));

        $response->assertRedirect();
    }

    public function test_invoke_redirects_when_xenforo_required_but_google_requested(): void
    {
        config(['tracker.xenforo_oauth_required' => true]);

        $response = $this->get(route('auth.oauth-redirect', ['provider' => 'google']));

        $response->assertRedirect();
    }

    public function test_invoke_allows_xenforo_when_xenforo_required(): void
    {
        config(['tracker.xenforo_oauth_required' => true]);

        $response = $this->get(route('auth.oauth-redirect', ['provider' => 'xenforo']));

        $response->assertRedirect();
    }
}
