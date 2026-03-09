<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_event_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.update', ['event' => $event->id]));

        $response->assertOk();
        $response->assertViewIs('pages.admin.events.update');
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.update', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
