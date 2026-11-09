<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\EventTypes\Commands;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_deletes_an_unused_type(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event_type = EventType::factory()->create();

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.delete', ['event_type' => $event_type]));

        $response->assertRedirect(route('admin.event-types.index'));
        $this->assertDatabaseMissing('tt_event_types', [EventType::ID => $event_type->id]);
    }

    public function test_invoke_keeps_a_type_events_use(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event_type = EventType::factory()->create();
        Event::factory()->create([Event::TYPE => $event_type->slug]);

        $response = $this->actingAs($trooper)
            ->post(route('admin.event-types.delete', ['event_type' => $event_type]));

        $response->assertRedirect();
        $response->assertSessionHas('danger');
        $this->assertDatabaseHas('tt_event_types', [EventType::ID => $event_type->id]);
    }
}
