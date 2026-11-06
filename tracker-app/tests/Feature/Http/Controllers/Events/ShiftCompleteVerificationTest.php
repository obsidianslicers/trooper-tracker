<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Http\Controllers\Events\ShiftCompleteController;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ShiftCompleteVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_flagged_trooper_confirmation_waits_for_verification(): void
    {
        [$trooper, $organization, $event_trooper] = $this->setUpShift(untrusted: true);

        $response = $this->confirm($trooper, $event_trooper, EventTrooperStatus::ATTENDED);

        $response->assertOk();
        $response->assertSee(ShiftCompleteController::VERIFICATION_MESSAGE);
        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $fresh->status);
        $this->assertSame([$organization->id], $fresh->requested_organization_ids);
        $this->assertNull($fresh->costume_organization_ids);
        $this->assertNotNull($fresh->verification_requested_at);
    }

    public function test_trusted_trooper_confirmation_is_unchanged(): void
    {
        [$trooper, , $event_trooper] = $this->setUpShift(untrusted: false);

        $this->confirm($trooper, $event_trooper, EventTrooperStatus::ATTENDED)->assertOk();

        $this->assertSame(EventTrooperStatus::ATTENDED, $event_trooper->fresh()->status);
    }

    public function test_link_cannot_change_a_troop_waiting_for_verification(): void
    {
        [$trooper, $organization, $event_trooper] = $this->setUpShift(untrusted: false);
        $event_trooper->requestVerification([$organization->id]);
        $event_trooper->save();

        $this->confirm($trooper, $event_trooper, EventTrooperStatus::ATTENDED)->assertOk();

        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $fresh->status);
        $this->assertSame([$organization->id], $fresh->requested_organization_ids);
    }

    public function test_flagged_trooper_club_selection_waits_for_verification(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->asUntrusted()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $event = Event::factory()->withOrganization($org1)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $response = $this->actingAs($trooper)->post(route('events.shift-complete-club-select', [
            'event_trooper' => $event_trooper->id,
        ]), [
            'encrypted_status' => Crypt::encryptString(EventTrooperStatus::ATTENDED->value),
            'organization_ids' => [$org1->id, $org2->id],
        ]);

        $response->assertOk();
        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $fresh->status);
        $this->assertEqualsCanonicalizing([$org1->id, $org2->id], $fresh->requested_organization_ids);
        $this->assertNull($fresh->costume_organization_ids);
    }

    /**
     * @return array{0: Trooper, 1: Organization, 2: EventTrooper}
     */
    private function setUpShift(bool $untrusted): array
    {
        $factory = Trooper::factory()->asActive()->withVerifiedEmail();
        $trooper = ($untrusted ? $factory->asUntrusted() : $factory)->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();

        return [$trooper, $organization, $event_trooper];
    }

    private function confirm(Trooper $trooper, EventTrooper $event_trooper, EventTrooperStatus $status): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => Crypt::encryptString($status->value),
        ]));
    }
}
