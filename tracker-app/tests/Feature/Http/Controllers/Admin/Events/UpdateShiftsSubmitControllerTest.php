<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateShiftsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_shifts_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                0 => [
                    'date' => now()->toDateString(),
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/' . $event->id . '/shifts', []);

        $response->assertRedirect(route('auth.login'));
    }
}
