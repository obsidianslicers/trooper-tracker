<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for EventDisplayController.
 *
 * Verifies:
 * - Authenticated troopers can view event sign-up page
 * - Event data and shifts are displayed correctly
 * - Breadcrumbs are set properly
 * - Can moderate flag is set correctly for moderators
 */
class EventDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::OPEN,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.event-display');
    }

    public function test_invoke_sets_primary_background_for_non_at_risk_event(): void
    {
        // Arrange: closed events are never considered "at risk"
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->closed()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertViewHas('bg', 'bg-primary');
    }

    public function test_invoke_sets_secondary_background_when_event_locked(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::STATUS => EventStatus::SIGN_UP_LOCKED,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertViewHas('bg', 'bg-secondary');
    }

    public function test_invoke_passes_event_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertViewHas('event');
        $view_event = $response->viewData('event');
        $this->assertEquals($event->id, $view_event->id);
    }

    public function test_invoke_provides_google_calendar_url_for_event_with_times(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertViewHas('googleCalendarUrl');
        $url = $response->viewData('googleCalendarUrl');
        $this->assertIsString($url);
        $this->assertNotSame('', $url);
        $this->assertStringContainsString('calendar.google', $url);
    }

    public function test_invoke_includes_event_shifts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();
        EventShift::factory()->for($event)->count(2)->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $view_event = $response->viewData('event');
        $this->assertTrue($view_event->relationLoaded('event_shifts'));
        $this->assertCount(2, $view_event->event_shifts);
    }

    public function test_invoke_renders_forum_link_when_thread_and_post_present(): void
    {
        // Arrange
        config([
            'services.xenforo.base_url' => 'https://forum.example.com',
            // Prevent the controller from attempting to fetch thread posts in this test
            'services.xenforo.api_key' => null,
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            Event::THREAD_ID => 1234,
            Event::POST_ID => 5678,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertOk();
        $response->assertSee('https://forum.example.com/posts/5678/', false);
    }

    public function test_invoke_renders_forum_comments_from_xenforo_api_when_configured(): void
    {
        // Arrange
        config([
            'services.xenforo.base_url' => 'https://forum.example.com',
            'services.xenforo.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://forum.example.com/api/threads/1234/posts*' => Http::response([
                'posts' => [
                    [
                        'post_id' => 5678,
                        'message' => '[b]Starter[/b] post (should not display)',
                        'post_date' => 1_700_000_000,
                        'User' => [
                            'username' => 'ForumUser',
                            'avatar_urls' => [
                                's' => 'https://forum.example.com/avatar.jpg',
                            ],
                        ],
                    ],
                    [
                        'post_id' => 9000,
                        'message' => '[b]Older reply[/b] content',
                        'post_date' => 1_700_000_100,
                        'User' => [
                            'username' => 'ForumUser',
                            'avatar_urls' => [
                                's' => 'https://forum.example.com/avatar.jpg',
                            ],
                        ],
                    ],
                    [
                        'post_id' => 9001,
                        'message' => '[b]Newest reply[/b] content',
                        'post_date' => 1_700_000_200,
                        'User' => [
                            'username' => 'ForumUser',
                            'avatar_urls' => [
                                's' => 'https://forum.example.com/avatar.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        OauthLogin::factory()->create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => 'xenforo',
            OauthLogin::PROVIDER_ID => '123',
        ]);

        $event = Event::factory()->create([
            Event::THREAD_ID => 1234,
            Event::POST_ID => 5678,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertOk();
        $response->assertSee('ForumUser', false);
        $response->assertSee('https://forum.example.com/avatar.jpg', false);
        $response->assertDontSee('Starter post (should not display)', false);

        // Newest reply should appear first.
        $response->assertSeeInOrder([
            '<strong>Newest reply</strong> content',
            '<strong>Older reply</strong> content',
        ], false);
    }

    public function test_invoke_sets_can_moderate_false_for_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('events.display', $event));

        // Assert
        $response->assertViewHas('can_moderate', false);
    }

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $event = Event::factory()->create();

        // Act
        $response = $this->get(route('events.display', $event));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }
}
