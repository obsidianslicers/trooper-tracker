<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowSignUpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_signup_page(): void
    {
        $response = $this->get(route('auth.signup'));

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/SignUp')
            ->has('oauth')
            ->has('oauth.session')
            ->has('oauth.providers')
            ->where('oauth.required', null)
            ->has('oauth.email_password')
        );
    }
}
