<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Events\EventClosed;
use App\Events\EventPosted;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Listeners\ArchiveEventThread;
use TroopTracker\Plugins\Xenforo\Listeners\CreateEventThread;
use TroopTracker\Plugins\Xenforo\Models\EventTypeForum;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;
use TroopTracker\Plugins\Xenforo\Services\ForumThreadMessageService;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

class EventTypeForumsTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
        config([
            'plugins.xenforo.base_url' => 'https://forums.example.com',
            'plugins.xenforo.api_key' => 'abc',
        ]);
    }

    public function test_an_event_types_forum_wins_over_the_organizations(): void
    {
        $organization = $this->createForumOrganization([OrganizationForum::EVENT_FORUM_ID => 42]);
        $event_type = EventType::factory()->create();
        EventTypeForum::store($event_type->id, [EventTypeForum::EVENT_FORUM_ID => 99]);
        $event = Event::factory()
            ->withOrganization($organization)
            ->create([Event::TYPE => $event_type->slug]);

        $messages = Mockery::mock(ForumThreadMessageService::class);
        $messages->shouldReceive('buildThreadMessage')->andReturn('body');
        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(1);
        $xenforo->shouldReceive('create_thread')
            ->once()
            ->with(99, $event->name, 'body', 1)
            ->andReturn(['status' => 201, 'body' => ['thread' => ['thread_id' => 5, 'first_post_id' => 6]]]);

        (new CreateEventThread($xenforo, $messages))->handle(new EventPosted($event));
    }

    public function test_archiving_falls_back_to_the_organization_when_the_type_has_no_archive(): void
    {
        $organization = $this->createForumOrganization([OrganizationForum::ARCHIVE_FORUM_ID => 77]);
        $with_archive = EventType::factory()->create();
        $without_archive = EventType::factory()->create();
        EventTypeForum::store($with_archive->id, [EventTypeForum::ARCHIVE_FORUM_ID => 88]);
        EventTypeForum::store($without_archive->id, [EventTypeForum::EVENT_FORUM_ID => 99]);

        $first = $this->linkThread(Event::factory()->withOrganization($organization)
            ->create([Event::TYPE => $with_archive->slug]), 10, 11);
        $second = $this->linkThread(Event::factory()->withOrganization($organization)
            ->create([Event::TYPE => $without_archive->slug]), 20, 21);

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('move_thread')->once()->with(10, 88)->andReturn(['status' => 200]);
        $xenforo->shouldReceive('move_thread')->once()->with(20, 77)->andReturn(['status' => 200]);

        $subject = new ArchiveEventThread($xenforo);
        $subject->handle(new EventClosed($first));
        $subject->handle(new EventClosed($second));
    }

    public function test_the_update_event_type_page_shows_the_forum_section(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event_type = EventType::factory()->create();
        EventTypeForum::store($event_type->id, [EventTypeForum::EVENT_FORUM_ID => 314]);

        $response = $this->actingAs($trooper)
            ->get(route('admin.event-types.update', ['event_type' => $event_type]));

        $response->assertInertia(fn (Assert $page) => $page->where(
            'plugin_slots',
            function ($slots): bool
            {
                $html = (string) collect($slots)->get('admin.event-types.update.sections', '');

                return str_contains($html, 'Save forums') && str_contains($html, 'value="314"');
            },
        ));
    }

    public function test_the_forum_section_shows_without_an_api_key(): void
    {
        config(['plugins.xenforo.api_key' => null]);
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event_type = EventType::factory()->create();

        $response = $this->actingAs($trooper)
            ->get(route('admin.event-types.update', ['event_type' => $event_type]));

        $response->assertInertia(fn (Assert $page) => $page->where(
            'plugin_slots',
            fn ($slots): bool => str_contains(
                (string) collect($slots)->get('admin.event-types.update.sections', ''),
                'Save forums',
            ),
        ));
    }

    public function test_administrators_save_an_event_types_forums(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->withVerifiedEmail()->create();
        $event_type = EventType::factory()->create();

        $response = $this->actingAs($trooper)
            ->post(route('plugins.xenforo.admin.event-types.forum', ['event_type' => $event_type]), [
                'xenforo_event_forum_id' => 222,
                'xenforo_archive_forum_id' => '',
            ]);

        $response->assertRedirect(route('admin.event-types.update', ['event_type' => $event_type]));
        $forum = EventTypeForum::forEventType($event_type->id);
        $this->assertSame(222, $forum->event_forum_id);
        $this->assertNull($forum->archive_forum_id);
    }
}
