<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClosedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_the_completed_events(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->asClosed()->create([Event::NAME => 'Library day']);
        EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->get(route('events.closed'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/PastEvents')
            ->where('status', 'closed')
            ->where('lookback_days', null)
            ->has('events', 1)
            ->where('events.0.name', 'Library day')
            ->where('events.0.shifts', 1)
            ->where('totals.shifts', 1)
            ->where('pagination.current_page', 1)
            ->where('pagination.next_url', null)
            ->has('hosting_organizations'));
    }

    public function test_invoke_pages_the_history(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        Event::factory()->asClosed()->count(26)->create();

        $this->actingAs($trooper)->get(route('events.closed'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events', 25)
                ->where('pagination.last_page', 2)
                ->where('pagination.total', 26)
                ->where('pagination.next_url', fn (?string $url) => str_contains((string) $url, 'page=2')));

        $this->actingAs($trooper)->get(route('events.closed', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events', 1)
                ->where('pagination.current_page', 2));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.closed'));

        $response->assertRedirect(route('auth.login'));
    }
}
