<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_login_page_for_guests(): void
    {
        $response = $this->get(route('auth.login'));

        $response->assertOk();
        $response->assertViewIs('pages.auth.login');
    }

    public function test_invoke_redirects_authenticated_trooper_to_home(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('auth.login'));

        $response->assertRedirect(route('home'));
    }
}
