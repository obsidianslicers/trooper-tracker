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
            //  exactly the providers and the required one: nothing from the session
            ->has('oauth', fn (Assert $oauth) => $oauth
                ->has('providers')
                ->where('required', null)
            )
        );
    }
}
