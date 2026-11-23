<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Messages\Events\Queries\GetTroopsToConfirm;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\TestCase;

class GetTroopsToConfirmTest extends TestCase
{
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    public function test_lists_going_and_tentative_sign_ups_on_ended_shifts_oldest_first(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $newer = $this->troopToConfirm($trooper, EventTrooperStatus::TENTATIVE, now()->subDays(2));
        $older = $this->troopToConfirm($trooper, EventTrooperStatus::GOING, now()->subDays(10));

        $result = GetTroopsToConfirm::call(trooper: $trooper);

        $this->assertSame([$older->id, $newer->id], $result->pluck('id')->all());
        $this->assertSame('Holiday Parade', $result->first()->event_shift->event->name);
    }

    public function test_leaves_out_answered_and_cancelled_sign_ups(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        foreach ([EventTrooperStatus::ATTENDED, EventTrooperStatus::UNABLE_TO_ATTEND, EventTrooperStatus::CANCELLED, EventTrooperStatus::STAND_BY] as $status)
        {
            $this->troopToConfirm($trooper, $status);
        }

        $this->assertCount(0, GetTroopsToConfirm::call(trooper: $trooper));
    }

    public function test_leaves_out_a_troop_sent_for_verification(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troop = $this->troopToConfirm($trooper);
        $troop->forceFill([EventTrooper::REQUESTED_ORGANIZATION_IDS => [1]])->saveQuietly();

        $this->assertCount(0, GetTroopsToConfirm::call(trooper: $trooper));
    }

    public function test_leaves_out_troops_past_the_grace_period(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->troopToConfirm($trooper, ended_at: now()->subDays(31));

        $this->assertCount(0, GetTroopsToConfirm::call(trooper: $trooper));
    }

    public function test_leaves_out_shifts_that_are_still_open(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troop = $this->troopToConfirm($trooper);
        $troop->event_shift->forceFill([EventShift::STATUS => EventStatus::OPEN])->saveQuietly();

        $this->assertCount(0, GetTroopsToConfirm::call(trooper: $trooper));
    }

    public function test_leaves_out_other_troopers_sign_ups(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->troopToConfirm(Trooper::factory()->asActive()->create());

        $this->assertCount(0, GetTroopsToConfirm::call(trooper: $trooper));
    }

    public function test_runs_one_query_when_there_is_nothing_to_confirm(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $this->expectsDatabaseQueryCount(1);

        GetTroopsToConfirm::call(trooper: $trooper);
    }
}
