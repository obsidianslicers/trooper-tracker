<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Messages\Events\Queries\GetEventsForModerator;
use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetEventsForModeratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_moderated_events_for_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        $outside_org = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $allowed_event = Event::factory()->withOrganization($org)->create();
        Event::factory()->withOrganization($outside_org)->create();

        $result = (new GetEventsForModerator(new EventFilter(new Request), $moderator))->handle();

        $this->assertCount(1, $result->items());
        $this->assertSame($allowed_event->id, $result->items()[0]->id);
    }

    public function test_handle_returns_all_events_for_administrator(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        Event::factory()->count(3)->create();

        $result = (new GetEventsForModerator(new EventFilter(new Request), $administrator))->handle();

        $this->assertCount(3, $result->items());
    }

    public function test_handle_returns_only_their_own_events_for_event_coordinator(): void
    {
        $coordinator = Trooper::factory()->asEventCoordinator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($coordinator)->forOrganization($org)->asMember()->create();

        $own_event = Event::factory()->withOrganization($org)->withCreatedByTrooper($coordinator)->create();
        Event::factory()->withOrganization($org)->create();

        $result = (new GetEventsForModerator(new EventFilter(new Request), $coordinator))->handle();

        $this->assertCount(1, $result->items());
        $this->assertSame($own_event->id, $result->items()[0]->id);
    }
}
