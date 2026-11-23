<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Reports\PageData;

use App\Messages\Reports\PageData\CostumeEventSummaryCsvPageData;
use App\Messages\Reports\PageData\DonationEventSummaryCsvPageData;
use App\Messages\Reports\PageData\TrooperEventSummaryCsvPageData;
use App\Messages\Reports\Queries\GetCostumeEventSummary;
use App\Messages\Reports\Queries\GetDonationEventSummary;
use App\Messages\Reports\Queries\GetTrooperEventSummary;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SummaryCsvPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_costume_csv_lists_every_costume_and_the_totals(): void
    {
        $organization = Organization::factory()->create(['name' => 'Florida Garrison']);
        $costume = Costume::factory()->create(['name' => 'Stormtrooper']);
        $costume->events_count = 2;
        $costume->uses_count = 5;
        $fake = $this->fakeMessages([GetCostumeEventSummary::class => $this->page([$costume])]);

        $subject = new CostumeEventSummaryCsvPageData(
            actor: $this->moderator(),
            date_start: '2026-01-01',
            organization_id: $organization->id,
        );
        $result = $subject->handle();

        $this->assertStringStartsWith('costume-event-summary-', $result['filename']);
        $this->assertSame([
            ['Club: Florida Garrison', 'From: 2026-01-01'],
            ['Costume', 'Unique Events', 'Total Uses'],
            ['Stormtrooper', 2, 5],
            [],
            ['Total', 2, 5],
        ], $result['rows']);
        $fake->assertCalled(
            GetCostumeEventSummary::class,
            fn (array $params): bool => $params['page_size'] === PHP_INT_MAX && $params['sort'] === 'uses_count'
        );
    }

    public function test_trooper_csv_notes_active_members_only(): void
    {
        $trooper = Trooper::factory()->create(['display_name' => 'TK-1']);
        $trooper->events_count = 3;
        $trooper->event_shifts_count = 4;
        $this->fakeMessages([GetTrooperEventSummary::class => $this->page([$trooper])]);

        $subject = new TrooperEventSummaryCsvPageData(actor: $this->moderator(), active_only: '1');
        $result = $subject->handle();

        $this->assertSame([
            ['Active Members Only'],
            ['Trooper', 'Unique Events', 'Total Shifts'],
            ['TK-1', 3, 4],
            [],
            ['Total', 3, 4],
        ], $result['rows']);
    }

    public function test_donation_csv_reads_every_page_and_totals_the_shifts(): void
    {
        $first = $this->eventWithShift('Library Visit', direct: 100, attendees: 2);
        $second = $this->eventWithShift('Hospital Visit', direct: 50, attendees: 1);
        $fake = $this->fakeMessages([
            GetDonationEventSummary::class => fn (array $params): LengthAwarePaginator => new LengthAwarePaginator(
                [$params['page'] === 1 ? $first : $second], 2, 1, $params['page']
            ),
        ]);

        $subject = new DonationEventSummaryCsvPageData(actor: $this->moderator(), charity_only: '1');
        $rows = $subject->handle()['rows']->all();

        $this->assertSame(['Charity Data Only'], $rows[0]);
        $this->assertSame(DonationEventSummaryCsvPageData::HEADINGS, $rows[1]);
        $this->assertSame('Library Visit', $rows[2][0]);
        $this->assertSame('Hospital Visit', $rows[3][0]);
        $this->assertSame([], $rows[4]);
        $this->assertSame(['Total', '', '', '', '', 150.0, 0.0, 150.0, 4, 3, 6, ''], $rows[5]);
        $fake->assertCalled(GetDonationEventSummary::class, times: 2);
    }

    private function eventWithShift(string $name, int $direct, int $attendees): Event
    {
        $event = Event::factory()->create(['name' => $name]);
        $shift = EventShift::factory()
            ->forEvent($event)
            ->withCharityData(direct_funds: $direct)
            ->create(['charity_hours' => 2]);
        $shift->attendees_count = $attendees;
        $event->setRelation('event_shifts', collect([$shift]));

        return $event;
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
        return new LengthAwarePaginator($items, count($items), PHP_INT_MAX, 1);
    }
}
