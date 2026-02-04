<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTypeCountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_moderator_or_administrator(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.event-type-count'));

        $response->assertForbidden();
    }

    public function test_invoke_displays_event_type_count_view(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.reports.event-type-count'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.event-type-count');
    }

    public function test_invoke_passes_event_types_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.event-type-count'));

        $response->assertOk();
        $response->assertViewHas('event_types');
    }

    public function test_invoke_passes_lookback_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.event-type-count'));

        $response->assertOk();
        $response->assertViewHas('lookback');

        $lookback = $response->viewData('lookback');
        $this->assertEquals(30, $lookback);
    }

    public function test_invoke_groups_events_by_type(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        // Create events of different types
        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::TYPE => EventType::REGULAR,
            Event::CREATED_ID => $administrator->id,
        ]);
        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::TYPE => EventType::REGULAR,
            Event::CREATED_ID => $administrator->id,
        ]);

        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::TYPE => EventType::CHARITY,
            Event::CREATED_ID => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('admin.reports.event-type-count'));

        $response->assertOk();
        $event_types = $response->viewData('event_types');

        // Should have entries for each type with events
        $this->assertGreaterThan(0, $event_types->count());
    }
}
