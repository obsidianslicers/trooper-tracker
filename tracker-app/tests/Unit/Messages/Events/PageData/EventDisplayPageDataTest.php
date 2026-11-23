<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Messages\Events\PageData\EventDisplayPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\EventWatch;
use App\Models\Trooper;
use App\Plugins\PluginSmilies;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDisplayPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_each_part_as_its_own_closure(): void
    {
        $subject = new EventDisplayPageData(Trooper::factory()->asActive()->create(), Event::factory()->create());
        $result = $subject->handle();

        $this->assertSame(
            ['event', 'shifts', 'my_sign_ups', 'uploads', 'is_watching', 'tools', 'tour', 'event_slots'],
            array_keys($result)
        );
        $this->assertContainsOnlyInstancesOf(Closure::class, $result);
    }

    public function test_handle_says_whether_the_viewer_is_watching(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        $this->assertFalse($this->part($trooper, $event, 'is_watching'));

        EventWatch::create([EventWatch::EVENT_ID => $event->id, EventWatch::TROOPER_ID => $trooper->id]);

        $this->assertTrue($this->part($trooper, $event, 'is_watching'));
    }

    public function test_handle_gives_moderators_the_roster_tools_and_members_only_watch(): void
    {
        $moderator = Trooper::factory()->asSystemOwner()->asActive()->create();
        $member = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([Event::STATUS => EventStatus::OPEN]);

        $tools = $this->part($moderator, $event, 'tools')->resolve();
        $this->assertArrayNotHasKey('can_moderate', $tools);
        $this->assertSame(route('events.download-roster-csv', ['event' => $event]), $tools['roster_csv_url']);
        $this->assertSame(route('events.share-roster', ['event' => $event]), $tools['share_roster_url']);
        $this->assertSame(route('events.broadcast-message', ['event' => $event]), $tools['broadcast_url']);

        $tools = $this->part($member, $event, 'tools')->resolve();
        $this->assertNull($tools['roster_csv_url']);
        $this->assertNull($tools['share_roster_url']);
        $this->assertNull($tools['broadcast_url']);
        $this->assertSame(route('events.toggle-watch', ['event' => $event]), $tools['watch_url']);
    }

    public function test_handle_splits_the_photos_into_instructional_and_mission_review(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $map = EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => true]);
        $photo = EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => false]);

        $uploads = $this->part($trooper, $event, 'uploads')->resolve();

        $this->assertSame([$map->id], array_column($uploads['instructional'], 'id'));
        $this->assertArrayNotHasKey('is_instructional', $uploads['instructional'][0]);
        $this->assertSame([$photo->id], array_column($uploads['mission_review'], 'id'));
        $this->assertNull($uploads['instructional'][0]['tag_url']);
        $this->assertNotNull($uploads['mission_review'][0]['tag_url']);
    }

    public function test_handle_lists_the_viewers_own_sign_ups(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $sign_up = EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asGoing()->create();
        EventTrooper::factory()->forEventShift($shift)->asGoing()->create();

        $summary = $this->part($trooper, $event, 'my_sign_ups')->resolve();

        $this->assertTrue($summary['has_open_shift']);
        $this->assertSame([$shift->id], $summary['open_shift_ids']);
        $this->assertSame([$sign_up->id], array_column($summary['sign_ups'], 'id'));
        $this->assertSame(EventTrooperStatus::GOING->value, $summary['sign_ups'][0]['status']['value']);
        //  one shift, so no time beside it
        $this->assertNull($summary['sign_ups'][0]['time']);
    }

    public function test_handle_shows_a_plugins_smilies_in_the_mission_brief(): void
    {
        app(PluginSmilies::class)->set(fn (): array => [
            ['title' => 'Smile', 'code' => ':)', 'image_url' => 'https://forums.example.com/smile.png'],
        ]);
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([Event::COMMENTS => 'Bring water :)']);

        $brief = $this->part($trooper, $event, 'event')->resolve()['mission_brief'];

        $this->assertStringContainsString('<img src="https://forums.example.com/smile.png"', $brief['html']);
        $this->assertFalse($brief['is_required']);
    }

    private function part(Trooper $trooper, Event $event, string $name): mixed
    {
        return (new EventDisplayPageData($trooper, $event))->handle()[$name]();
    }
}
