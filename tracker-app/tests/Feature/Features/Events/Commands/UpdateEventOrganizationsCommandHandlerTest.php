<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Commands;

use App\Features\Events\Commands\UpdateEventOrganizationsCommand;
use App\Features\Events\Commands\UpdateEventOrganizationsCommandHandler;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UpdateEventOrganizationsCommandHandler
 */
class UpdateEventOrganizationsCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_resets_organizations_not_in_data_to_cannot_attend(): void
    {
        $event = Event::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $event->organizations()->attach($org1->id, [
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 10,
        ]);
        $event->organizations()->attach($org2->id, [
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 5,
        ]);

        $data = [
            $org1->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 15,
            ],
        ];

        $command = new UpdateEventOrganizationsCommand(
            event: $event,
            data: $data
        );
        $handler = app(UpdateEventOrganizationsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::CAN_ATTEND => false,
            EventOrganization::TROOPERS_ALLOWED => null,
            EventOrganization::HANDLERS_ALLOWED => null,
        ]);
    }

    public function test_invoke_updates_organizations_in_data(): void
    {
        $event = Event::factory()->create();
        $organization = Organization::factory()->create();

        $event->organizations()->attach($organization->id, [
            EventOrganization::CAN_ATTEND => false,
            EventOrganization::TROOPERS_ALLOWED => null,
        ]);

        $data = [
            $organization->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 20,
                EventOrganization::HANDLERS_ALLOWED => 2,
            ],
        ];

        $command = new UpdateEventOrganizationsCommand(
            event: $event,
            data: $data
        );
        $handler = app(UpdateEventOrganizationsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $organization->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 20,
            EventOrganization::HANDLERS_ALLOWED => 2,
        ]);
    }

    public function test_invoke_handles_multiple_organizations(): void
    {
        $event = Event::factory()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        $event->organizations()->attach($org1->id);
        $event->organizations()->attach($org2->id);
        $event->organizations()->attach($org3->id);

        $data = [
            $org1->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => 10,
            ],
            $org2->id => [
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::HANDLERS_ALLOWED => 3,
            ],
        ];

        $command = new UpdateEventOrganizationsCommand(
            event: $event,
            data: $data
        );
        $handler = app(UpdateEventOrganizationsCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org1->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::TROOPERS_ALLOWED => 10,
        ]);
        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org2->id,
            EventOrganization::CAN_ATTEND => true,
            EventOrganization::HANDLERS_ALLOWED => 3,
        ]);
        $this->assertDatabaseHas('tt_event_organizations', [
            EventOrganization::EVENT_ID => $event->id,
            EventOrganization::ORGANIZATION_ID => $org3->id,
            EventOrganization::CAN_ATTEND => false,
        ]);
    }

    public function test_invoke_returns_null(): void
    {
        $event = Event::factory()->create();

        $command = new UpdateEventOrganizationsCommand(
            event: $event,
            data: []
        );
        $handler = app(UpdateEventOrganizationsCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result);
    }
}
