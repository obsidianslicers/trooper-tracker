<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_logs_out_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('auth.logout'));

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_invoke_redirects_guest(): void
    {
        $response = $this->get(route('auth.logout'));

        $response->assertRedirect();
        $this->assertGuest();
    }
}
