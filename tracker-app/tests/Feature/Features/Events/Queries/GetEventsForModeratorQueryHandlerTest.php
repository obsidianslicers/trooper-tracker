<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsForModeratorQuery;
use App\Features\Events\Queries\GetEventsForModeratorQueryHandler;
use App\Models\Event;
use App\Models\Filters\EventFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetEventsForModeratorQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_only_moderated_events_for_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();
        $outside_org = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($org)->asModerator()->create();

        $allowed_event = Event::factory()->withOrganization($org)->create();
        Event::factory()->withOrganization($outside_org)->create();

        $subject = new GetEventsForModeratorQueryHandler();

        $result = $subject(new GetEventsForModeratorQuery(new EventFilter(new Request()), $moderator));

        $this->assertCount(1, $result->items());
        $this->assertSame($allowed_event->id, $result->items()[0]->id);
    }

    public function test_invoke_returns_all_events_for_administrator(): void
    {
        $administrator = Trooper::factory()->asAdministrator()->create();

        Event::factory()->count(3)->create();

        $subject = new GetEventsForModeratorQueryHandler();

        $result = $subject(new GetEventsForModeratorQuery(new EventFilter(new Request()), $administrator));

        $this->assertCount(3, $result->items());
    }
}
