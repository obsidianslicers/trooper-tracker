<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_blade_home_page_for_guests_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('pages.home');
    }

    public function test_invoke_displays_inertia_home_page_for_guests_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page): Assert => $page->component('Home'));
    }

    public function test_invoke_redirects_authenticated_troopers_to_events_list(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('home'));

        $response->assertRedirect(route('events.list'));
    }
}
