<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelledControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_cancelled_events_page(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('events.cancelled'));

        $response->assertOk();
        $response->assertViewIs('pages.events.cancelled');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.cancelled'));

        $response->assertRedirect(route('auth.login'));
    }
}
