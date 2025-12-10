<?php

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingTroopsHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_upcoming_shifts_for_authenticated_user(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $older_shift = EventShift::factory()
            ->open()
            ->create();

        $newer_shift = EventShift::factory()
            ->open()
            ->create();

        EventTrooper::factory()->withShift($older_shift)->withTrooper($trooper)->create();
        // EventTrooper::factory()->withShift($newer_shift)->withTrooper($trooper)->create();

        // Act
        $response = $this->get(route('dashboard.upcoming-troops-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.upcoming-troops');
        $response->assertViewHas('upcoming_shifts', function ($collection) use ($older_shift)
        {
            return $collection->count() === 1
                && $collection->first()->is($older_shift);
        });
    }

    public function test_invoke_displays_upcoming_shifts_for_another_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $other_shift = EventShift::factory()->open()->create();

        EventTrooper::factory()->withShift($other_shift)->withTrooper($other_trooper)->create();

        // Act
        $response = $this->get(route('dashboard.upcoming-troops-htmx', ['trooper_id' => $other_trooper->id]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.upcoming-troops');
        $response->assertViewHas('upcoming_shifts', function ($collection) use ($other_shift)
        {
            return $collection->count() === 1
                && $collection->first()->is($other_shift);
        });
    }

    public function test_invoke_shows_no_troops_if_none_exist(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        // Act
        $response = $this->get(route('dashboard.upcoming-troops-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.upcoming-troops');
        $response->assertViewHas('upcoming_shifts', function ($collection)
        {
            return $collection->isEmpty();
        });
    }
}
