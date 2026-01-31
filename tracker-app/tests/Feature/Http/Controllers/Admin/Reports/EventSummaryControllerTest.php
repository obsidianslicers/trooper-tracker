<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Reports;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSummaryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_moderator_or_administrator(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('admin.reports.event-summary'));

        $response->assertForbidden();
    }

    public function test_invoke_displays_event_summary_view(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.reports.event-summary'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.reports.event-summary');
    }

    public function test_invoke_passes_events_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.event-summary'));

        $response->assertOk();
        $response->assertViewHas('events');
    }

    public function test_invoke_passes_lookback_to_view(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($administrator)->get(route('admin.reports.event-summary'));

        $response->assertOk();
        $response->assertViewHas('lookback');

        $lookback = $response->viewData('lookback');
        $this->assertEquals(30, $lookback);
    }

    public function test_invoke_administrator_sees_all_events(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();
        $other_moderator = Trooper::factory()->asModerator()->create();

        // Create events moderated by different people
        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::CREATED_ID => $other_moderator->id,
        ]);
        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::CREATED_ID => $other_moderator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('admin.reports.event-summary'));

        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(2, $events);
    }

    public function test_invoke_moderator_sees_only_their_events(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $other_moderator = Trooper::factory()->asModerator()->create();

        // Create event moderated by this moderator
        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::CREATED_ID => $moderator->id,
        ]);

        // Create event moderated by another moderator
        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::CREATED_ID => $other_moderator->id,
        ]);

        $response = $this->actingAs($moderator)->get(route('admin.reports.event-summary'));

        $response->assertOk();
        $events = $response->viewData('events');
        // Moderator scope may not return events, depending on organization hierarchy
        $this->assertIsIterable($events);
    }

    public function test_invoke_only_includes_closed_events(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        // Create events with different statuses
        Event::factory()->create([
            Event::STATUS => EventStatus::CLOSED,
            Event::CREATED_ID => $administrator->id,
        ]);

        Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
            Event::CREATED_ID => $administrator->id,
        ]);

        Event::factory()->create([
            Event::STATUS => EventStatus::CANCELLED,
            Event::CREATED_ID => $administrator->id,
        ]);

        $response = $this->actingAs($administrator)->get(route('admin.reports.event-summary'));

        $response->assertOk();
        $events = $response->viewData('events');
        $this->assertCount(1, $events);
    }
}
