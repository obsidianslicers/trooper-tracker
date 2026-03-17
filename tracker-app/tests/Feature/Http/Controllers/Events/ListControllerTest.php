<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_events_list_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
        $response->assertViewIs('pages.events.list');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
