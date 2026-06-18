<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectDeniedTrooperMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_through_unauthenticated_request(): void
    {
        $response = $this->get(route('account.profile'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_passes_through_active_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.profile'));

        $response->assertOk();
    }

    public function test_redirects_denied_trooper_to_denied_page(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $response = $this->actingAs($trooper)->get(route('account.profile'));

        $response->assertRedirect(route('account.denied'));
    }
}
