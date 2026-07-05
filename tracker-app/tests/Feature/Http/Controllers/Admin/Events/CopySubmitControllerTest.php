<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CopySubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_copies_event_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/copy', [
            'name' => 'Copied Event Name',
            'event_start' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect();
    }

    public function test_invoke_resets_forum_and_notification_state_on_copied_event(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $event = Event::factory()
            ->withForumThreadEnabled()
            ->withCreateNotificationsSent()
            ->withCancelNotificationsSent()
            ->state([
                Event::THREAD_ID => 321,
                Event::POST_ID => 654,
            ])
            ->create();

        $this->actingAs($trooper)->post('/admin/events/' . $event->id . '/copy', [
            'name' => 'Copied Event Name',
            'event_start' => now()->addDays(7)->toDateString(),
        ]);

        $event_copy = Event::query()
            ->whereKeyNot($event->id)
            ->where(Event::NAME, 'Copied Event Name')
            ->firstOrFail();

        $this->assertTrue($event_copy->create_forum_thread);
        $this->assertNull($event_copy->thread_id);
        $this->assertNull($event_copy->post_id);
        $this->assertNull($event_copy->create_notifications_sent_at);
        $this->assertNull($event_copy->cancel_notifications_sent_at);
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post('/admin/events/' . $event->id . '/copy', [
            'name' => 'Copied Event Name',
            'event_start' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
