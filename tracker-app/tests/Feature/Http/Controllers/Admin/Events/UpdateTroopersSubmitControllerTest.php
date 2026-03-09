<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTroopersSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_trooper_statuses_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/troopers', [
            'troopers' => [],
        ]);

        $response->assertRedirect(route('admin.events.troopers', ['event' => $event->id]));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/' . $event->id . '/troopers', []);

        $response->assertRedirect(route('auth.login'));
    }
}
