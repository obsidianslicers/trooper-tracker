<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_home_page_for_guests(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('pages.home');
    }

    public function test_invoke_redirects_authenticated_troopers_to_events_list(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('home'));

        $response->assertRedirect(route('events.list'));
    }
}
