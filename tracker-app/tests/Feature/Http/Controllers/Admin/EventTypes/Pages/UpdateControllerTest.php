<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\EventTypes\Pages;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_shows_the_event_type_and_how_many_events_use_it(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $event_type = EventType::factory()->uncounted()->create();
        Event::factory()->count(2)->create([Event::TYPE => $event_type->slug]);

        $response = $this->actingAs($trooper)
            ->get(route('admin.event-types.update', ['event_type' => $event_type]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/event-types/Update')
            ->where('event_type.id', $event_type->id)
            ->where('event_type.is_counted', false)
            ->where('event_type.events_count', 2));
    }

    public function test_create_page_renders_for_administrators(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.event-types.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('admin/event-types/Create'));
    }
}
