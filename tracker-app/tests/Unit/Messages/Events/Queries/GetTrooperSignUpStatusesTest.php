<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Queries;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Queries\GetTrooperSignUpStatuses;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperSignUpStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_the_troopers_live_sign_ups_keyed_by_shift(): void
    {
        $trooper = Trooper::factory()->create();
        $going = EventShift::factory()->create();
        $tentative = EventShift::factory()->create();
        $cancelled = EventShift::factory()->create();
        $someone_else = EventShift::factory()->create();

        EventTrooper::factory()->forEventShift($going)->forTrooper($trooper)->asGoing()->create();
        EventTrooper::factory()->forEventShift($tentative)->forTrooper($trooper)->asTentative()->create();
        EventTrooper::factory()->forEventShift($cancelled)->forTrooper($trooper)->create([
            EventTrooper::STATUS => EventTrooperStatus::CANCELLED,
        ]);
        EventTrooper::factory()->forEventShift($someone_else)->asGoing()->create();

        $subject = new GetTrooperSignUpStatuses(
            $trooper,
            [$going->id, $tentative->id, $cancelled->id, $someone_else->id],
        );
        $result = $subject->handle();

        $this->assertSame([$going->id, $tentative->id], $result->keys()->sort()->values()->all());
        $this->assertSame(EventTrooperStatus::GOING, $result[$going->id]);
        $this->assertSame(EventTrooperStatus::TENTATIVE, $result[$tentative->id]);
    }

    public function test_handle_returns_nothing_without_shifts(): void
    {
        $subject = new GetTrooperSignUpStatuses(Trooper::factory()->create(), []);

        $this->assertTrue($subject->handle()->isEmpty());
    }
}
