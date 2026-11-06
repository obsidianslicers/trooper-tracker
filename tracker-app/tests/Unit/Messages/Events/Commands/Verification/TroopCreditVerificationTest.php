<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands\Verification;

use App\Enums\EventTrooperStatus;
use App\Messages\Events\Commands\Verification\ApproveTroopCredit;
use App\Messages\Events\Commands\Verification\RejectTroopCredit;
use App\Messages\Events\Commands\Verification\RequestTroopVerification;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TroopCreditVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_then_approve_saves_credit(): void
    {
        [$event_trooper, $organization] = $this->eventTrooper();

        (new RequestTroopVerification($event_trooper, [$organization->id]))->handle();
        (new ApproveTroopCredit($event_trooper, $organization->id))->handle();

        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::ATTENDED, $fresh->status);
        $this->assertSame([$organization->id], $fresh->costume_organization_ids);
    }

    public function test_approve_refuses_a_club_that_was_not_requested(): void
    {
        [$event_trooper, $organization] = $this->eventTrooper();
        (new RequestTroopVerification($event_trooper, [$organization->id]))->handle();

        $this->expectException(RuntimeException::class);

        (new ApproveTroopCredit($event_trooper, $organization->id + 1000))->handle();
    }

    public function test_reject_refuses_a_club_that_was_not_requested(): void
    {
        [$event_trooper, $organization] = $this->eventTrooper();

        $this->expectException(RuntimeException::class);

        (new RejectTroopCredit($event_trooper, $organization->id))->handle();
    }

    /**
     * @return array{0: EventTrooper, 1: Organization}
     */
    private function eventTrooper(): array
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        return [EventTrooper::factory()->forEventShift($event_shift)->asGoing()->create(), $organization];
    }
}
