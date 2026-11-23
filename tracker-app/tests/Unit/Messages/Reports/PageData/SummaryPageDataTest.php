<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Reports\PageData;

use App\Messages\Reports\PageData\CostumeEventSummaryPageData;
use App\Messages\Reports\PageData\DonationEventSummaryPageData;
use App\Messages\Reports\PageData\TrooperEventSummaryPageData;
use App\Messages\Reports\Queries\GetCostumeEventSummary;
use App\Messages\Reports\Queries\GetDonationEventSummary;
use App\Messages\Reports\Queries\GetTrooperEventSummary;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SummaryPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_trooper_summary_lists_troopers_and_describes_the_filters(): void
    {
        $organization = Organization::factory()->create(['name' => 'Florida Garrison']);
        $trooper = Trooper::factory()->create(['display_name' => 'TK-1']);
        $trooper->events_count = 3;
        $trooper->event_shifts_count = 0;

        $fake = $this->fakeMessages([GetTrooperEventSummary::class => $this->page([$trooper])]);

        $subject = new TrooperEventSummaryPageData(
            actor: $this->moderator(),
            date_start: '2026-01-01',
            active_only: '1',
            organization_id: $organization->id,
            sort: 'events_count',
            dir: 'asc',
        );

        $result = $subject->handle();

        $fake->assertCalled(GetTrooperEventSummary::class, fn (array $params): bool => $params['active_only']
            && $params['organization']->is($organization)
            && $params['date_start']->eq(Carbon::parse('2026-01-01'))
            && $params['page_size'] === TrooperEventSummaryPageData::PAGE_SIZE);
        $row = $result['troopers']->resolve()[0];

        $this->assertSame('TK-1', $row['display_name']);
        $this->assertSame('3', $row['events_count']);
        $this->assertSame('-', $row['event_shifts_count']);
        $this->assertSame('2026-01-01', $result['filters']['date_start']);
        $this->assertSame(
            [['value' => $organization->id, 'label' => 'Florida Garrison']],
            $result['organizations']
        );
        $this->assertSame(
            'Florida Garrison — from Jan 01, 2026 — Active Members Only',
            $result['caption']
        );
        $this->assertSame('↑', $result['sorts']['events_count']['arrow']);
        $this->assertStringContainsString('dir=desc', $result['sorts']['events_count']['url']);
        $this->assertStringContainsString('dir=asc', $result['sorts']['display_name']['url']);
        $this->assertStringContainsString('format=csv', $result['csv_url']);
    }

    public function test_costume_summary_totals_the_page(): void
    {
        $costumes = collect([['Stormtrooper', 2, 5], ['Sandtrooper', 1, 1]])
            ->map(function (array $row): Costume
            {
                $costume = Costume::factory()->create(['name' => $row[0]]);
                $costume->events_count = $row[1];
                $costume->uses_count = $row[2];

                return $costume;
            });

        $this->fakeMessages([GetCostumeEventSummary::class => $this->page($costumes->all())]);

        $subject = new CostumeEventSummaryPageData(actor: $this->moderator());

        $result = $subject->handle();

        $this->assertSame('Stormtrooper', $result['costumes']->resolve()[0]['name']);
        $this->assertSame(['events_count' => '3', 'uses_count' => '6'], $result['totals']);
        $this->assertSame('All Clubs — all time', $result['caption']);
        $this->assertSame('↓', $result['sorts']['uses_count']['arrow']);
    }

    public function test_donation_summary_totals_each_event_and_the_page(): void
    {
        $garrison = Organization::factory()->create(['name' => 'Florida Garrison', 'sequence' => 1]);
        $squad = Organization::factory()->create(['name' => 'Makaze Squad', 'sequence' => 2]);
        $event = Event::factory()
            ->withOrganization($garrison)
            ->withEventStart(Carbon::parse('2026-06-01 10:00:00'))
            ->create(['name' => 'Library Visit']);
        $shifts = collect([[100, 25, 2], [50, 0, 4]])
            ->map(function (array $row) use ($event): EventShift
            {
                $shift = EventShift::factory()
                    ->forEvent($event)
                    ->withCharityData(direct_funds: $row[0], indirect_funds: $row[1])
                    ->create(['charity_hours' => 2]);
                $shift->attendees_count = $row[2];

                return $shift;
            });
        $event->setRelation('event_shifts', $shifts);

        $this->fakeMessages([GetDonationEventSummary::class => $this->page([$event])]);

        $subject = new DonationEventSummaryPageData(actor: $this->moderator(), charity_only: '1');

        $result = $subject->handle();
        $row = $result['events']->resolve()[0];

        $this->assertSame('Library Visit', $row['name']);
        $this->assertSame('Florida Garrison', $row['organization_name']);
        $this->assertCount(2, $row['shifts']);
        $this->assertSame('125', $row['shifts'][0]['total']);
        $this->assertSame('4', $row['shifts'][0]['trooper_hours']);
        $this->assertSame('175', $row['totals']['total']);
        $this->assertSame('12', $row['totals']['trooper_hours']);
        $this->assertSame('175', $result['grand_totals']['total']);
        $this->assertSame([$garrison->id, $squad->id], $result['filters']['organization_ids']);
        $this->assertCount(2, $result['clubs']);
        $this->assertSame('— Charity data only', $result['caption']);
    }

    public function test_donation_summary_has_no_totals_or_caption_when_empty(): void
    {
        Organization::factory()->create();
        $this->fakeMessages([GetDonationEventSummary::class => $this->page([])]);

        $subject = new DonationEventSummaryPageData(actor: $this->moderator());

        $result = $subject->handle();

        $this->assertNull($result['grand_totals']);
        $this->assertNull($result['caption']);
        $this->assertSame([], $result['clubs']);
    }

    private function moderator(): Trooper
    {
        return Trooper::factory()->asSystemOwner()->create();
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function page(array $items): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 50, 1);
    }
}
