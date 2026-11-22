<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData\Admin;

use App\Messages\Events\PageData\Admin\CopyEventPageData;
use App\Messages\Events\PageData\Admin\ListAdminEventsPageData;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\Filters\EventFilter;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ListAdminEventsPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_lists_events_with_their_shifts_and_what_the_actor_may_do(): void
    {
        $admin = Trooper::factory()->asSystemOwner()->create();
        $event = Event::factory()->create();
        EventShift::factory()->count(2)->forEvent($event)->create();
        $request = Request::create('/');
        $request->setUserResolver(fn () => $admin);

        $subject = new ListAdminEventsPageData($admin, new EventFilter($request), status: 'open');
        $result = $subject->handle();
        $row = collect($result['events']->toArray($request))->firstWhere('id', $event->id);

        $this->assertSame(2, $row['shift_count']);
        $this->assertTrue($row['can_update']);
        $this->assertSame($event->organization->name, $row['organization']['name']);
        $this->assertSame('open', $result['filters']['status']);
        $this->assertNotEmpty($result['statuses']);
    }

    public function test_copy_page_data_starts_from_the_event(): void
    {
        $event = Event::factory()->create([
            Event::NAME => 'Parade',
            Event::EVENT_START => Carbon::parse('2030-05-04 09:30'),
        ]);

        $result = (new CopyEventPageData($event))->handle();

        $this->assertSame('COPY OF Parade', $result['event']['name']);
        $this->assertSame('2030-05-04 09:30', $result['event']['event_start']);
    }
}
