<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_displays_the_login_page()
    {
        $response = $this->get(route('auth.login'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.auth.login');
    }
}