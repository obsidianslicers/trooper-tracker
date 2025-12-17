<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_event_sign_up_page(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.signup', ['event' => $event->id]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.events.signup');
        $response->assertViewHas('event');
    }

    public function test_invoke_loads_event_with_shifts(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);
        EventShift::factory()->count(2)->create([
            'event_id' => $event->id,
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.signup', ['event' => $event->id]));

        // Assert
        $response->assertOk();
        $event = $response->viewData('event');
        $this->assertCount(2, $event->event_shifts);
    }

    public function test_invoke_loads_event_with_organization_details(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.signup', ['event' => $event->id]));

        // Assert
        $response->assertOk();
        $event = $response->viewData('event');
        $this->assertNotNull($event->organization);
    }

    public function test_invoke_includes_breadcrumb_navigation(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.signup', ['event' => $event->id]));

        // Assert
        $response->assertOk();
        $response->assertSee('Events');
    }

    public function test_invoke_denies_access_to_unauthenticated_user(): void
    {
        // Arrange
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);

        // Act
        $response = $this->get(route('events.signup', ['event' => $event->id]));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_404_for_nonexistent_event(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('events.signup', ['event' => 99999]));

        // Assert
        $response->assertNotFound();
    }

    public function test_invoke_allows_access_to_pending_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();
        $event = Event::factory()->create([
            'event_start' => Carbon::now()->addDays(10),
        ]);

        // Act
        $response = $this->actingAs($trooper)->get(route('events.signup', ['event' => $event->id]));

        // Assert
        $response->assertOk();
    }
}
