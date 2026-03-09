<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SignUpEmailControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_stores_registration_auth_in_session_and_redirects(): void
    {
        $response = $this->get(route('auth.signup-email'));

        $response->assertRedirect(route('auth.register'));
        $this->assertNotNull(Session::get('registration_auth'));
        $this->assertEquals('email', Session::get('registration_auth')['method']);
    }

    public function test_invoke_redirects_when_xenforo_oauth_required(): void
    {
        config(['tracker.xenforo_oauth_required' => true]);

        $response = $this->get(route('auth.signup-email'));

        $response->assertRedirect();
    }
}
