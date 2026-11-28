<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsTroopCorrections;
use Tests\TestCase;

class ShowTroopCorrectionFormControllerTest extends TestCase
{
    use BuildsTroopCorrections;
    use RefreshDatabase;

    public function test_invoke_without_a_search_shows_only_the_lookup(): void
    {
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.troop-correction-form'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/RequestCredit')
            ->where('searched', false)
            ->where('events', [])
            ->where('event', null));
    }

    public function test_invoke_finds_completed_events_within_a_day_of_the_date(): void
    {
        $shift = $this->completedShift();
        $this->completedShift(name: 'Much Later Troop')->event
            ->update(['event_start' => now()->subDays(10), 'event_end' => now()->subDays(10)]);
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $date = $shift->event->event_start->addDay()->toDateString();
        $response = $this->actingAs($trooper)->get(route('events.troop-correction-form', ['date' => $date]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('searched', true)
            ->has('events', 1)
            ->where('events.0.id', $shift->event_id)
            ->where('events.0.can_request', true));
    }

    public function test_invoke_finds_events_by_title(): void
    {
        $shift = $this->completedShift(name: 'Lakefront Charity Walk');
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.troop-correction-form', ['search' => 'charity']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.name', 'Lakefront Charity Walk'));
    }

    public function test_invoke_marks_an_event_the_trooper_has_credit_for(): void
    {
        $shift = $this->completedShift();
        $trooper = Trooper::factory()->withVerifiedEmail()->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $response = $this->actingAs($trooper)->get(route('events.troop-correction-form', ['search' => 'Parade']));

        $response->assertInertia(fn (Assert $page) => $page->where('events.0.can_request', false));
    }

    public function test_invoke_with_an_event_sends_its_shifts_and_credit_options(): void
    {
        $shift = $this->completedShift();
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.troop-correction-form', ['event_id' => $shift->event_id]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('event.id', $shift->event_id)
            ->where('event.shifts.0.id', $shift->id)
            ->has('credit_options.costumes')
            ->has('credit_options.clubs')
            ->has('credit_options.no_costume_club_ids'));
    }

    public function test_invoke_shows_what_a_plugin_adds_under_the_results(): void
    {
        View::addNamespace('fixtures', base_path('tests/Fixtures/views'));
        app(PluginSlots::class)->add('events.troop-correction.no-match', 'fixtures::troop-correction-no-match');
        $trooper = Trooper::factory()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.troop-correction-form', ['search' => 'nothing here']));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('no_match_html', fn (string $html) => str_contains($html, 'Tell us about an outside troop')));
    }
}
