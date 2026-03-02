<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Shares;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the ShareEventRosterController.
 *
 * Validates that the shared event roster page displays correctly,
 * increments view count, and handles various share states.
 */
class ShareEventRosterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_share_roster_page(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
        ]);

        // Act
        $response = $this->get(route('shares.roster', ['share' => $share->share_token]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.shares.roster');
        $response->assertViewHas('share', function ($view_share) use ($share)
        {
            return $view_share->id === $share->id;
        });
        $response->assertViewHas('event', function ($view_event) use ($event)
        {
            return $view_event->id === $event->id;
        });
    }

    public function test_invoke_increments_view_count(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
            EventShare::VIEW_COUNT => 5,
        ]);

        // Act
        $this->get(route('shares.roster', ['share' => $share->share_token]));

        // Assert
        $this->assertEquals(6, $share->fresh()->view_count);
    }

    public function test_invoke_displays_going_troopers_in_roster(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
            EventShare::EXPIRES_AT => now()->addDay(),
            EventShare::IS_REVOKED => false,
        ]);
        $shift = EventShift::factory()->for($event)->create();

        $trooper = Trooper::factory()->create([
            Trooper::LEGAL_NAME => 'John Doe',
        ]);

        EventTrooper::factory()->create([
            EventTrooper::EVENT_SHIFT_ID => $shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::STATUS => EventTrooperStatus::GOING,
        ]);

        // Act
        $response = $this->get(route('shares.roster', ['share' => $share->share_token]));

        // Assert
        $response->assertOk();
        $response->assertSeeText('John Doe');
    }

    public function test_invoke_renders_page_for_expired_share(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
            EventShare::EXPIRES_AT => now()->subDay(),
        ]);

        // Act
        $response = $this->get(route('shares.roster', ['share' => $share->share_token]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.shares.roster');
    }

    public function test_invoke_renders_page_for_revoked_share(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
            EventShare::IS_REVOKED => true,
        ]);

        // Act
        $response = $this->get(route('shares.roster', ['share' => $share->share_token]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.shares.roster');
    }

    public function test_invoke_increments_view_count_even_for_expired_share(): void
    {
        // Arrange
        $event = Event::withoutEvents(fn() => Event::factory()->create());
        $share = EventShare::factory()->create([
            EventShare::EVENT_ID => $event->id,
            EventShare::EXPIRES_AT => now()->subDay(),
            EventShare::VIEW_COUNT => 0,
        ]);

        // Act
        $this->get(route('shares.roster', ['share' => $share->share_token]));

        // Assert
        $this->assertEquals(1, $share->fresh()->view_count);
    }

    public function test_invoke_returns_404_for_invalid_share_token(): void
    {
        // Act
        $response = $this->get(route('shares.roster', ['share' => 'invalid-token-12345']));

        // Assert
        $response->assertNotFound();
    }
}
