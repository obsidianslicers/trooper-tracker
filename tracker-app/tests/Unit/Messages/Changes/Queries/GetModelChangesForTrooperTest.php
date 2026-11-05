<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Changes\Queries;

use App\Messages\Changes\Queries\GetModelChangesForTrooper;
use App\Models\EventTrooper;
use App\Models\ModelChange;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetModelChangesForTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_trooper_model_changes(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $change = ModelChange::factory()
            ->forTrooper($trooper)
            ->withFieldName('email')
            ->createdAt(now()->subDays(3))
            ->create();

        $result = (new GetModelChangesForTrooper($trooper, 7))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($change->id, $result->first()->id);
    }

    public function test_handle_returns_event_trooper_changes_for_trooper(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $event_trooper = EventTrooper::factory()->forTrooper($trooper)->create();

        $change = ModelChange::factory()
            ->forEventTrooper($event_trooper)
            ->withFieldName('status')
            ->createdAt(now()->subDays(2))
            ->create();

        $result = (new GetModelChangesForTrooper($trooper, 7))->handle();

        $this->assertCount(1, $result);
        $this->assertSame($change->id, $result->first()->id);
    }

    public function test_handle_excludes_changes_outside_lookback_period_when_int(): void
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

        $result = (new GetModelChangesForTrooper($trooper, 7))->handle();

        $this->assertCount(1, $result);
    }

    public function test_handle_converts_string_lookback_to_carbon(): void
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

        $result = (new GetModelChangesForTrooper($trooper, '2026-02-01'))->handle();

        $this->assertCount(1, $result);
    }

    public function test_handle_accepts_carbon_instance_for_lookback(): void
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

        $result = (new GetModelChangesForTrooper($trooper, Carbon::parse('2026-02-01')))->handle();

        $this->assertCount(1, $result);
    }

    public function test_handle_excludes_changes_for_other_troopers(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        ModelChange::factory()->forTrooper($trooper)->createdAt(now()->subDays(2))->create();
        ModelChange::factory()->forTrooper($other_trooper)->createdAt(now()->subDays(2))->create();

        $result = (new GetModelChangesForTrooper($trooper, 7))->handle();

        $this->assertCount(1, $result);
    }

    public function test_handle_excludes_event_trooper_changes_for_other_troopers(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        $event_trooper = EventTrooper::factory()->forTrooper($trooper)->create();
        $other_event_trooper = EventTrooper::factory()->forTrooper($other_trooper)->create();

        ModelChange::factory()->forEventTrooper($event_trooper)->createdAt(now()->subDays(2))->create();
        ModelChange::factory()->forEventTrooper($other_event_trooper)->createdAt(now()->subDays(2))->create();

        $result = (new GetModelChangesForTrooper($trooper, 7))->handle();

        $this->assertCount(1, $result);
    }
}
