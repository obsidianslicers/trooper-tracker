<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData;

use App\Messages\Events\PageData\SharedEventPageData;
use App\Messages\Events\PageData\SharedRosterPageData;
use App\Messages\Events\Queries\GetSharedEventRoster;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\EventUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SharedPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_shared_event_previews_the_club_logo(): void
    {
        $event = Event::factory()->create();

        $subject = new SharedEventPageData(event: $event);
        $result = $subject->handle();

        $this->assertSame(route('shares.og-image', $event->organization->id), $result['image_url']);
        $this->assertFalse($result['sharing_image']);
        $this->assertNull($result['event_upload']);
    }

    public function test_shared_photo_previews_the_photo(): void
    {
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->create(['event_id' => $event->id]);

        $result = (new SharedEventPageData(event: $event, event_upload: $upload))->handle();

        $this->assertTrue($upload->is($result['event_upload']));
        $this->assertSame($upload->large_url, $result['image_url']);
        $this->assertTrue($result['sharing_image']);
    }

    public function test_shared_photo_of_another_event_is_ignored(): void
    {
        $event = Event::factory()->create();
        $upload = EventUpload::factory()->create(['event_id' => Event::factory()->create()->id]);

        $result = (new SharedEventPageData(event: $event, event_upload: $upload))->handle();

        $this->assertNull($result['event_upload']);
        $this->assertFalse($result['sharing_image']);
    }

    public function test_shared_roster_returns_the_share_and_its_roster(): void
    {
        $share = EventShare::factory()->forEvent(Event::factory()->create())->create();
        $roster = Event::factory()->make();
        $this->fakeMessages([GetSharedEventRoster::class => $roster]);

        $result = (new SharedRosterPageData(share: $share))->handle();

        $this->assertSame(['share' => $share, 'event' => $roster], $result);
    }
}
