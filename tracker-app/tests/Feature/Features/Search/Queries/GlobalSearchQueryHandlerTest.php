<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Search\Queries;

use App\Features\Search\Queries\GlobalSearchQuery;
use App\Features\Search\Queries\GlobalSearchQueryHandler;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_empty_collections_for_short_term(): void
    {
        Trooper::factory()->withDisplayName('Visible Trooper')->create();
        Event::factory()->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('a', 'all'));

        $this->assertCount(0, $result['troopers']);
        $this->assertCount(0, $result['events']);
    }

    public function test_invoke_finds_troopers_by_display_name_and_email(): void
    {
        Trooper::factory()
            ->withDisplayName('Darth Finder')
            ->withEmail('darth.finder@example.test')
            ->create();
        Trooper::factory()
            ->withDisplayName('Clone Trooper')
            ->withEmail('clone@example.test')
            ->create();

        $subject = new GlobalSearchQueryHandler;

        $by_name = $subject(new GlobalSearchQuery('Darth', 'troopers'));
        $by_email = $subject(new GlobalSearchQuery('finder@example', 'troopers'));

        $this->assertSame(['Darth Finder'], $by_name['troopers']->pluck(Trooper::DISPLAY_NAME)->all());
        $this->assertSame(['Darth Finder'], $by_email['troopers']->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_finds_troopers_regardless_of_word_order(): void
    {
        Trooper::factory()->withDisplayName('Matthew Drennan')->create();
        Trooper::factory()->withDisplayName('Other Trooper')->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('drennan matthew', 'troopers'));

        $this->assertSame(['Matthew Drennan'], $result['troopers']->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_orders_troopers_by_relevance(): void
    {
        Trooper::factory()->withDisplayName('Anakin Skywalker')->create();
        Trooper::factory()->withDisplayName('Skywalker Ranch')->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Skywalker', 'troopers'));

        $this->assertSame(
            ['Skywalker Ranch', 'Anakin Skywalker'],
            $result['troopers']->pluck(Trooper::DISPLAY_NAME)->all()
        );
    }

    public function test_invoke_falls_back_to_any_token_match_when_full_search_finds_nothing(): void
    {
        Trooper::factory()->withDisplayName('Matthew Drennan')->create();
        Trooper::factory()->withDisplayName('Other Trooper')->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Matthew Smith', 'troopers'));

        $this->assertSame(['Matthew Drennan'], $result['troopers']->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_finds_troopers_by_legal_name(): void
    {
        Trooper::factory()
            ->withDisplayName('Callsign Only')
            ->withLegalName('Anakin Skywalker')
            ->create();
        Trooper::factory()
            ->withDisplayName('Other Trooper')
            ->withLegalName('CT-7567 Rex')
            ->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Skywalker', 'troopers'));

        $this->assertSame(['Callsign Only'], $result['troopers']->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_finds_troopers_by_identifier_and_ignores_soft_deleted_memberships(): void
    {
        $active_org = Organization::factory()->withName('Active Org')->create();
        $deleted_org = Organization::factory()->withName('Deleted Org')->create();

        $active_trooper = Trooper::factory()->withDisplayName('Identifier Active')->create();
        $deleted_trooper = Trooper::factory()->withDisplayName('Identifier Deleted')->create();

        TrooperOrganization::factory()
            ->forTrooper($active_trooper)
            ->forOrganization($active_org)
            ->withIdentifier('TK-421')
            ->create();

        $deleted_membership = TrooperOrganization::factory()
            ->forTrooper($deleted_trooper)
            ->forOrganization($deleted_org)
            ->withIdentifier('TK-421')
            ->create();
        $deleted_membership->delete();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('TK-421', 'troopers'));

        $this->assertSame(['Identifier Active'], $result['troopers']->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_with_all_type_returns_troopers_and_events(): void
    {
        Trooper::factory()->withDisplayName('Dual Match Trooper')->create();
        Event::factory()->state([Event::NAME => 'Dual Match Event'])->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Dual Match', 'all'));

        $this->assertCount(1, $result['troopers']);
        $this->assertCount(1, $result['events']);
    }

    public function test_invoke_with_troopers_type_does_not_return_events(): void
    {
        Trooper::factory()->withDisplayName('Trooper Only Match')->create();
        Event::factory()->state([Event::NAME => 'Trooper Only Match Event'])->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Trooper Only Match', 'troopers'));

        $this->assertCount(1, $result['troopers']);
        $this->assertCount(0, $result['events']);
    }

    public function test_invoke_with_events_type_does_not_return_troopers(): void
    {
        Trooper::factory()->withDisplayName('Event Only Match Trooper')->create();
        Event::factory()->state([Event::NAME => 'Event Only Match'])->create();

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Event Only Match', 'events'));

        $this->assertCount(1, $result['events']);
        $this->assertCount(0, $result['troopers']);
    }

    public function test_invoke_limits_trooper_results_to_twenty_five_sorted_by_display_name(): void
    {
        for ($i = 1; $i <= 30; $i++)
        {
            Trooper::factory()->withDisplayName(sprintf('Search Trooper %02d', $i))->create();
        }

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Search Trooper', 'troopers'));

        $this->assertCount(25, $result['troopers']);
        $this->assertSame('Search Trooper 01', $result['troopers']->first()->display_name);
        $this->assertSame('Search Trooper 25', $result['troopers']->last()->display_name);
    }

    public function test_invoke_limits_event_results_to_twenty_five_sorted_by_event_start_desc(): void
    {
        for ($i = 1; $i <= 30; $i++)
        {
            Event::factory()->state([
                Event::NAME => 'Search Event',
                Event::EVENT_START => Carbon::create(2026, 1, 1, 12, 0, 0)->addDays($i),
            ])->create();
        }

        $subject = new GlobalSearchQueryHandler;

        $result = $subject(new GlobalSearchQuery('Search Event', 'events'));

        $this->assertCount(25, $result['events']);
        $this->assertTrue($result['events']->first()->event_start->gt($result['events']->last()->event_start));
        $this->assertSame(
            Carbon::create(2026, 1, 31, 12, 0, 0)->toDateTimeString(),
            $result['events']->first()->event_start->toDateTimeString()
        );
    }
}
