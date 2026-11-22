<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListCancelledEventsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_events_cancelled_in_the_last_30_days(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        Event::factory()->create([
            Event::NAME => 'Rained out parade',
            Event::STATUS => EventStatus::CANCELLED,
            Event::EVENT_START => now()->subDays(5),
            Event::EVENT_END => now()->subDays(5)->addHours(2),
        ]);
        Event::factory()->create([
            Event::NAME => 'Long ago parade',
            Event::STATUS => EventStatus::CANCELLED,
            Event::EVENT_START => now()->subDays(60),
            Event::EVENT_END => now()->subDays(60)->addHours(2),
        ]);

        $response = $this->actingAs($trooper)->get(route('events.cancelled'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/PastEvents')
            ->where('status', 'cancelled')
            ->where('lookback_days', 30)
            ->has('events', 1)
            ->where('events.0.name', 'Rained out parade')
            ->where('pagination', null));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.cancelled'));

        $response->assertRedirect(route('auth.login'));
    }
}
