<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Changes\Queries;

use App\Features\Changes\Queries\GetModelChangesForTrooperQuery;
use App\Features\Changes\Queries\GetModelChangesForTrooperQueryHandler;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetModelChangesForTrooperQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_trooper_model_changes(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $change = ModelChange::factory()
            ->forTrooper($trooper)
            ->withFieldName('email')
            ->createdAt(now()->subDays(3))
            ->create();

        $subject = new GetModelChangesForTrooperQueryHandler();

        $result = $subject(new GetModelChangesForTrooperQuery($trooper, 7));

        $this->assertCount(1, $result);
        $this->assertSame($change->id, $result->first()->id);
    }

    public function test_invoke_returns_event_trooper_changes_for_trooper(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $event_trooper = EventTrooper::factory()->forTrooper($trooper)->create();

        $change = ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName('status')
            ->createdAt(now()->subDays(2))
            ->create();

        $subject = new GetModelChangesForTrooperQueryHandler();

        $result = $subject(new GetModelChangesForTrooperQuery($trooper, 7));

        $this->assertCount(1, $result);
        $this->assertSame($change->id, $result->first()->id);
    }

    public function test_invoke_excludes_changes_outside_lookback_period_when_int(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        ModelChange::factory()
            ->forTrooper($trooper)
            ->createdAt(now()->subDays(3))
            ->create();

        ModelChange::factory()
            ->forTrooper($trooper)
            ->createdAt(now()->subDays(10))
            ->create();

        $subject = new GetModelChangesForTrooperQueryHandler();

        $result = $subject(new GetModelChangesForTrooperQuery($trooper, 7));

        $this->assertCount(1, $result);
    }

    public function test_invoke_converts_string_lookback_to_carbon(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        ModelChange::factory()
            ->forTrooper($trooper)
            ->createdAt(Carbon::parse('2026-02-15'))
            ->create();

        ModelChange::factory()
            ->forTrooper($trooper)
            ->createdAt(Carbon::parse('2026-01-15'))
            ->create();

        $subject = new GetModelChangesForTrooperQueryHandler();

        $result = $subject(new GetModelChangesForTrooperQuery($trooper, '2026-02-01'));

        $this->assertCount(1, $result);
    }

    public function test_invoke_accepts_carbon_instance_for_lookback(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        ModelChange::factory()
            ->forTrooper($trooper)
            ->createdAt(Carbon::parse('2026-02-15'))
            ->create();

        ModelChange::factory()
            ->forTrooper($trooper)
            ->createdAt(Carbon::parse('2026-01-15'))
            ->create();

        $subject = new GetModelChangesForTrooperQueryHandler();

        $result = $subject(new GetModelChangesForTrooperQuery($trooper, Carbon::parse('2026-02-01')));

        $this->assertCount(1, $result);
    }

    public function test_invoke_excludes_changes_for_other_troopers(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        ModelChange::factory()->forTrooper($trooper)->createdAt(now()->subDays(2))->create();
        ModelChange::factory()->forTrooper($other_trooper)->createdAt(now()->subDays(2))->create();

        $subject = new GetModelChangesForTrooperQueryHandler();

        $result = $subject(new GetModelChangesForTrooperQuery($trooper, 7));

        $this->assertCount(1, $result);
    }

    public function test_invoke_excludes_event_trooper_changes_for_other_troopers(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        $event_trooper = EventTrooper::factory()->forTrooper($trooper)->create();
        $other_event_trooper = EventTrooper::factory()->forTrooper($other_trooper)->create();

        ModelChange::factory()->forEventTrooper($event_trooper)->createdAt(now()->subDays(2))->create();
        ModelChange::factory()->forEventTrooper($other_event_trooper)->createdAt(now()->subDays(2))->create();

        $subject = new GetModelChangesForTrooperQueryHandler();

        $result = $subject(new GetModelChangesForTrooperQuery($trooper, 7));

        $this->assertCount(1, $result);
    }
}
