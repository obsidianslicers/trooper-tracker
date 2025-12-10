<?php

namespace Tests\Feature\Http\Controllers\Dashboard;

use App\Enums\EventStatus;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoricalTroopsHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_historical_shifts_for_authenticated_user(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $older_shift = EventShift::factory()
            ->closed()
            ->create();

        $newer_shift = EventShift::factory()
            ->closed()
            ->create();

        EventTrooper::factory()->withShift($older_shift)->withTrooper($trooper)->create();
        EventTrooper::factory()->withShift($newer_shift)->withTrooper($trooper)->create();

        // Act
        $response = $this->get(route('dashboard.historical-troops-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.historical-troops');
        $response->assertViewHas('historical_shifts', function ($collection) use ($older_shift, $newer_shift)
        {
            return $collection->count() === 2;
        });
    }

    public function test_invoke_displays_historical_shifts_for_another_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $other_trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        $other_shift = EventShift::factory()->closed()->create();

        EventTrooper::factory()->withShift($other_shift)->withTrooper($other_trooper)->create();

        // Act
        $response = $this->get(route('dashboard.historical-troops-htmx', ['trooper_id' => $other_trooper->id]));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.historical-troops');
        $response->assertViewHas('historical_shifts', function ($collection) use ($other_shift)
        {
            return $collection->count() === 1
                && $collection->first()->is($other_shift);
        });
    }

    public function test_invoke_shows_no_troops_if_none_exist(): void
    {
        // Arrange
        $user = Trooper::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->get(route('dashboard.historical-troops-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.dashboard.historical-troops');
        $response->assertViewHas('historical_shifts', function ($collection)
        {
            return $collection->isEmpty();
        });
    }
}
