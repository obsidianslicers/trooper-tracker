<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Search\Resources;

use App\Enums\EventStatus;
use App\Messages\Search\Resources\SearchEventResource;
use App\Messages\Search\Resources\SearchTrooperResource;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_trooper_lists_only_the_club_ids_they_hold(): void
    {
        $legion = Organization::factory()->create([
            Organization::NAME => '501st Legion',
            Organization::IDENTIFIER_DISPLAY => 'TKID',
        ]);
        $rebels = Organization::factory()->create([Organization::NAME => 'Rebel Legion']);
        $trooper = Trooper::factory()->withDisplayName('TK-421')->create();
        $trooper->organizations()->attach($legion->id, ['identifier' => '421']);
        $trooper->organizations()->attach($rebels->id, ['identifier' => null]);

        $subject = (new SearchTrooperResource($trooper->load('organizations')))->resolve();

        $this->assertSame('TK-421', $subject['display_name']);
        $this->assertSame([['club' => '501st Legion', 'label' => 'TKID: 421']], $subject['identifiers']);
        $this->assertSame(route('service-records.trooper', ['trooper' => $trooper]), $subject['url']);
    }

    public function test_event_shows_its_club_date_and_status(): void
    {
        $event = Event::factory()->create([
            Event::NAME => 'Parade',
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => '2026-01-05 10:00:00',
        ]);

        $subject = (new SearchEventResource($event->load('organization')))->resolve();

        $this->assertSame('Parade', $subject['name']);
        $this->assertSame('Jan 5, 2026', $subject['date']);
        $this->assertSame(EventStatus::OPEN->label(), $subject['status']);
        $this->assertSame($event->organization->name, $subject['organization']);
    }
}
