<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Enums\EventTrooperStatus;
use App\Events\TrooperSignupStatusChanged;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventTrooperObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_updated_fires_signup_status_changed_with_the_previous_status(): void
    {
        Queue::fake();
        $event_trooper = EventTrooper::factory()
            ->forTrooper(Trooper::factory()->create())
            ->create([EventTrooper::STATUS => EventTrooperStatus::GOING]);
        EventFacade::fake([TrooperSignupStatusChanged::class]);

        $event_trooper->status = EventTrooperStatus::CANCELLED;
        $event_trooper->save();

        EventFacade::assertDispatched(TrooperSignupStatusChanged::class, fn (TrooperSignupStatusChanged $changed): bool => $changed->signup->is($event_trooper)
            && $changed->previous_status === EventTrooperStatus::GOING);
    }

    public function test_updated_does_not_fire_signup_status_changed_for_other_fields_or_restores(): void
    {
        Queue::fake();
        $event_trooper = EventTrooper::factory()
            ->forTrooper(Trooper::factory()->create())
            ->create([EventTrooper::STATUS => EventTrooperStatus::CANCELLED]);
        $event_trooper->delete();
        EventFacade::fake([TrooperSignupStatusChanged::class]);

        $event_trooper->status = EventTrooperStatus::GOING;
        $event_trooper->restore();
        $event_trooper->is_handler = !$event_trooper->is_handler;
        $event_trooper->save();

        EventFacade::assertNotDispatched(TrooperSignupStatusChanged::class);
    }
}
