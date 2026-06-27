<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Carbon\Carbon;
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

    public function test_invoke_updates_parent_event_date_range_from_shifts(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()
            ->withEventStart(Carbon::parse('2026-05-01 10:00:00'))
            ->withEventEnd(Carbon::parse('2026-05-01 12:00:00'))
            ->create();
        $early_shift = EventShift::factory()->forEvent($event)->create();
        $late_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/shifts', [
            'shifts' => [
                $early_shift->id => [
                    'date' => '2026-06-03',
                    'starts_at' => '10:00',
                    'ends_at' => '12:00',
                ],
                $late_shift->id => [
                    'date' => '2026-06-05',
                    'starts_at' => '09:00',
                    'ends_at' => '17:30',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.events.shifts', ['event' => $event->id]));

        $event->refresh();

        $this->assertSame('2026-06-03 10:00:00', $event->event_start->toDateTimeString());
        $this->assertSame('2026-06-05 17:30:00', $event->event_end->toDateTimeString());
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/' . $event->id . '/shifts', []);

        $response->assertRedirect(route('auth.login'));
    }
}
