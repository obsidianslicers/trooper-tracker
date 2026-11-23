<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Enums\EventStatus;
use App\Messages\Events\PageData\ListEventsPageData;
use App\Messages\Support\Queries\GetSupportGoal;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ListEventsPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_marks_the_shifts_the_trooper_signed_up_for(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = $this->upcomingEvent();
        $mine = $this->shift($event, 1);
        $other = $this->shift($event, 2);
        $trooping = [EventTrooper::IS_HANDLER => false];
        EventTrooper::factory()->forEventShift($mine)->forTrooper($trooper)->asGoing()->create($trooping);
        EventTrooper::factory()->forEventShift($other)->asGoing()->count(2)->create($trooping);

        $subject = new ListEventsPageData($trooper);
        $result = $this->resolve($subject->handle());

        $shifts = collect($result['events'][0]['shifts'])->keyBy('id');
        $this->assertSame('going', $shifts[$mine->id]['my_status']);
        $this->assertSame(1, $shifts[$mine->id]['troopers']);
        $this->assertNull($shifts[$other->id]['my_status']);
        $this->assertSame(2, $shifts[$other->id]['troopers']);
        $this->assertStringEndsWith('#shift-'.$other->id, $shifts[$other->id]['url']);
    }

    public function test_handle_sends_the_month_and_date_parts_for_grouping(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = $this->upcomingEvent();
        $this->shift($event, 1);

        $result = $this->resolve((new ListEventsPageData($trooper))->handle());

        $row = $result['events'][0];
        $this->assertSame($event->event_start->format('Y-m'), $row['month_key']);
        $this->assertSame($event->event_start->format('j'), $row['day']);
        $this->assertSame($event->organization_id, $row['organization_id']);
        $this->assertNotEmpty($row['logo_url']);
    }

    public function test_handle_includes_the_one_unread_notice(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->asGlobal()->withTitle('Armor check Saturday')->create([
            Notice::MESSAGE => 'Bring **both** boots.',
        ]);

        $result = $this->resolve((new ListEventsPageData($trooper))->handle());

        $this->assertSame(1, $result['notices']['count']);
        $this->assertSame($notice->id, $result['notices']['notice']['id']);
        $this->assertSame('Armor check Saturday', $result['notices']['notice']['title']);
        $this->assertStringContainsString('<strong>both</strong>', $result['notices']['notice']['html']);
    }

    public function test_handle_counts_several_unread_notices_without_detail(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        Notice::factory()->asGlobal()->count(2)->create();

        $result = $this->resolve((new ListEventsPageData($trooper))->handle());

        $this->assertSame(2, $result['notices']['count']);
        $this->assertNull($result['notices']['notice']);
    }

    public function test_handle_lists_every_club_with_whether_it_may_troop(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $allowed = Organization::factory()->asOrganization()->create();
        $not_allowed = Organization::factory()->asOrganization()->create();
        $event = $this->upcomingEvent();
        $this->shift($event, 1);
        $event->organizations()->attach($allowed->id, ['can_attend' => true]);

        $result = $this->resolve((new ListEventsPageData($trooper))->handle());

        $clubs = collect($result['events'][0]['organizations'])->keyBy('id');
        $this->assertTrue($clubs[$allowed->id]['can_attend']);
        $this->assertFalse($clubs[$not_allowed->id]['can_attend']);
        $this->assertSame($not_allowed->name, $clubs[$not_allowed->id]['name']);
    }

    /**
     * The page data as the Svelte page receives it, with every resource turned into JSON.
     */
    public function test_handle_has_no_support_goal_when_none_is_set(): void
    {
        config(['tracker.support.goal' => 0]);
        $trooper = Trooper::factory()->asActive()->create();

        $result = (new ListEventsPageData($trooper))->handle();

        $this->assertNull($result['support']);
    }

    public function test_handle_offers_the_support_goal_to_load_later(): void
    {
        config(['tracker.support.goal' => 500]);
        $trooper = Trooper::factory()->asActive()->create();
        $this->fakeMessages([GetSupportGoal::class => [
            'goal' => 500.0,
            'current' => 125.0,
            'progress' => 25.0,
            'message' => 'Support the garrison.',
            'url' => null,
        ]]);

        $result = (new ListEventsPageData($trooper))->handle();

        $this->assertInstanceOf(Closure::class, $result['support']);
        $this->assertSame(
            ['goal' => 500.0, 'progress' => 25.0, 'message' => 'Support the garrison.', 'url' => null],
            ($result['support'])()->resolve(),
        );
    }

    private function resolve(array $data): array
    {
        return json_decode(json_encode($data), true);
    }

    private function upcomingEvent(): Event
    {
        $start = now()->addDays(3)->startOfHour();

        return Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
            Event::EVENT_START => $start,
            Event::EVENT_END => $start->copy()->addHours(8),
        ]);
    }

    private function shift(Event $event, int $hours_in): EventShift
    {
        $start = $event->event_start->copy()->addHours($hours_in);

        return EventShift::factory()->forEvent($event)->create([
            EventShift::SHIFT_STARTS_AT => $start,
            EventShift::SHIFT_ENDS_AT => $start->copy()->addHour(),
        ]);
    }
}
