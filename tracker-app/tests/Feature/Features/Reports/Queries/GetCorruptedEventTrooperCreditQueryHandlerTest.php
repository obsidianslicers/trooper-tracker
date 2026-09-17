<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetCorruptedEventTrooperCreditQuery;
use App\Features\Reports\Queries\GetCorruptedEventTrooperCreditQueryHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCorruptedEventTrooperCreditQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_finds_attended_row_with_wiped_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => null, EventTrooper::IS_HANDLER => false]);

        $subject = new GetCorruptedEventTrooperCreditQueryHandler();

        $result = $subject(new GetCorruptedEventTrooperCreditQuery());

        $this->assertCount(1, $result);
    }

    public function test_invoke_finds_attended_row_with_empty_credit(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->withCostumeOrganizationIds([])
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $subject = new GetCorruptedEventTrooperCreditQueryHandler();

        $result = $subject(new GetCorruptedEventTrooperCreditQuery());

        $this->assertCount(1, $result);
    }

    public function test_invoke_excludes_attended_row_with_intact_credit(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $costume = Costume::factory()->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->withCostumeOrganizationIds([$org->id])
            ->asAttended()
            ->create();

        $subject = new GetCorruptedEventTrooperCreditQueryHandler();

        $result = $subject(new GetCorruptedEventTrooperCreditQuery());

        $this->assertCount(0, $result);
    }

    public function test_invoke_excludes_non_attended_row_with_no_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create([EventTrooper::COSTUME_ID => null]);

        $subject = new GetCorruptedEventTrooperCreditQueryHandler();

        $result = $subject(new GetCorruptedEventTrooperCreditQuery());

        $this->assertCount(0, $result);
    }

    public function test_invoke_filters_by_trooper_id(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $other_trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => null, EventTrooper::IS_HANDLER => false]);

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($other_trooper)
            ->asAttended()
            ->create([EventTrooper::COSTUME_ID => null, EventTrooper::IS_HANDLER => false]);

        $subject = new GetCorruptedEventTrooperCreditQueryHandler();

        $result = $subject(new GetCorruptedEventTrooperCreditQuery($trooper->id));

        $this->assertCount(1, $result);
        $this->assertSame($trooper->id, $result->first()->trooper_id);
    }
}
