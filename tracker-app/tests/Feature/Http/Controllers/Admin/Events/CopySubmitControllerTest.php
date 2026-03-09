<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopySubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_copies_event_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/copy', [
            'name' => 'Copied Event Name',
            'event_start' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect();
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/' . $event->id . '/copy', [
            'name' => 'Copied Event Name',
            'event_start' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
