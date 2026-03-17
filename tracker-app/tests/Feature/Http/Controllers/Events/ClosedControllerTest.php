<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_closed_events_page(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.closed'));

        $response->assertOk();
        $response->assertViewIs('pages.events.closed');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.closed'));

        $response->assertRedirect(route('auth.login'));
    }
}
