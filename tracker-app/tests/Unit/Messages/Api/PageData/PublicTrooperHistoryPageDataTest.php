<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Api\PageData;

use App\Messages\Api\PageData\PublicTrooperHistoryPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTrooperHistoryPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_closed_troops_for_trooper_found_by_id(): void
    {
        $trooper = Trooper::factory()->withDisplayName('Rex')->create();
        $event = $this->attendedClosedEvent($trooper);

        $subject = new PublicTrooperHistoryPageData(trooper_id: $trooper->id);
        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Rex', $result[0]['trooperName']);
        $this->assertSame(1, $result[0]['troopCount']);
        $this->assertSame($event->id, $result[0]['events'][0]['eventID']);
        $this->assertSame(
            ['trooperName', 'tkid', '501forum', 'rebelforum', 'events', 'troopCount'],
            array_keys($result[0]),
        );
    }

    public function test_handle_finds_trooper_by_identifier_and_club(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-42')
            ->create();

        $subject = new PublicTrooperHistoryPageData(identifier: 'TK-42', organization_id: $organization->id);
        $result = $subject->handle();

        $this->assertSame('TK-42', $result[0]['tkid']);
    }

    public function test_handle_returns_nothing_without_a_club_for_the_identifier(): void
    {
        $subject = new PublicTrooperHistoryPageData(identifier: 'TK-42');

        $this->assertSame([], $subject->handle());
    }

    public function test_handle_returns_nothing_for_unknown_trooper(): void
    {
        $subject = new PublicTrooperHistoryPageData(trooper_id: 9999);

        $this->assertSame([], $subject->handle());
    }

    private function attendedClosedEvent(Trooper $trooper): Event
    {
        $event = Event::factory()->asClosed()->create([Event::EVENT_TYPE_ID => EventType::idNamed('Regular')]);
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        return $event;
    }
}
