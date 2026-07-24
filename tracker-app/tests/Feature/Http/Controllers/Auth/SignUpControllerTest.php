<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SignUpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_signup_page(): void
    {
        $response = $this->get(route('auth.signup'));

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('auth/SignUp')
            ->has('oauth')
            ->has('oauth.session')
            ->has('oauth.xenforo')
            ->has('oauth.google')
            ->has('oauth.email_password')
        );
    }
}
