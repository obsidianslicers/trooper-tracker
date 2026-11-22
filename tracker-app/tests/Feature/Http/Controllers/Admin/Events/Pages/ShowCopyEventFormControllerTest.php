<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events\Pages;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowCopyEventFormControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_copy_event_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.events.copy.create', ['event' => $event->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/events/Copy')
            ->where('event.id', $event->id)
            ->where('event.name', "COPY OF {$event->name}"));
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->get(route('admin.events.copy.create', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
