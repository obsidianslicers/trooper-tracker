<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class TroopVerificationsControllerTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_queue_lists_only_troops_the_moderator_covers(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $covered = $this->awaiting($this->memberOf($this->garrison), [$this->garrison->id]);
        $other = $this->awaiting($this->memberOf($this->other_club), [$this->other_club->id]);

        $response = $this->actingAs($moderator)->get(route('admin.troopers.troop-verifications'));

        $response->assertOk();
        $response->assertSee($covered->trooper->display_name);
        $response->assertDontSee($other->trooper->display_name);
    }

    public function test_moderator_approves_their_club(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $event_trooper = $this->awaiting($this->memberOf($this->garrison), [$this->garrison->id]);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.troop-verifications.approve', [
                'event_trooper' => $event_trooper,
                'organization' => $this->garrison,
            ]))
            ->assertRedirect();

        $fresh = $event_trooper->fresh();
        $this->assertSame(EventTrooperStatus::ATTENDED, $fresh->status);
        $this->assertSame([$this->garrison->id], $fresh->costume_organization_ids);
        $this->assertNull($fresh->requested_organization_ids);
    }

    public function test_moderator_cannot_verify_another_club(): void
    {
        $moderator = $this->moderatorOf($this->other_club);
        $trooper = $this->memberOf($this->garrison);
        $this->memberOf($this->other_club, $trooper);
        $event_trooper = $this->awaiting($trooper, [$this->garrison->id, $this->other_club->id]);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.troop-verifications.approve', [
                'event_trooper' => $event_trooper,
                'organization' => $this->garrison,
            ]))
            ->assertForbidden();

        $this->assertSame(EventTrooperStatus::AWAITING_VERIFICATION, $event_trooper->fresh()->status);
    }

    public function test_moderator_removes_a_club(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $event_trooper = $this->awaiting($this->memberOf($this->garrison), [$this->garrison->id]);

        $this->actingAs($moderator)
            ->post(route('admin.troopers.troop-verifications.reject', [
                'event_trooper' => $event_trooper,
                'organization' => $this->garrison,
            ]))
            ->assertRedirect();

        $this->assertSame(EventTrooperStatus::NO_SHOW, $event_trooper->fresh()->status);
    }

    public function test_dashboard_counts_troops_waiting(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $this->awaiting($this->memberOf($this->garrison), [$this->garrison->id]);

        $this->actingAs($moderator)
            ->get(route('admin.display'))
            ->assertOk()
            ->assertSee('1 troop awaiting verification');
    }

    /**
     * @param  array<int, int>  $organization_ids
     */
    private function awaiting(Trooper $trooper, array $organization_ids): EventTrooper
    {
        $trooper->is_untrusted = true;
        $trooper->save();

        $event = Event::factory()->withOrganization($this->club)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();

        $event_trooper->requestVerification($organization_ids);
        $event_trooper->save();

        return $event_trooper;
    }
}
