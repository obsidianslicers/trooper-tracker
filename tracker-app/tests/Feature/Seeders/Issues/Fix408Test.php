<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\MembershipStatus;
use App\Mail\Fix408AmbiguousMemberships;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Database\Seeders\Issues\Fix408;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Seeders\Issues\Concerns\SeedsLegacyTables;
use Tests\TestCase;

class Fix408Test extends TestCase
{
    use RefreshDatabase;
    use SeedsLegacyTables;

    private const string IMPORTED_AT = '2026-05-29 08:34:05';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLegacyWorld();
    }

    public function test_corrects_a_flagged_false_membership(): void
    {
        [$trooper, $membership, $assignment] = $this->falseRebelMember(MembershipStatus::RETIRED);

        $this->runFix();

        $this->assertTrue($membership->refresh()->trashed());
        $this->assertFalse((bool) TrooperAssignment::withTrashed()->find($assignment->id)->is_member);
        Mail::assertNotQueued(Fix408AmbiguousMemberships::class);
    }

    public function test_strips_the_false_club_from_tt2_credit_and_re_resolves_when_nothing_is_left(): void
    {
        [$trooper] = $this->falseRebelMember(MembershipStatus::RETIRED);
        $partial = $this->attended($trooper, $this->shiftAt('2026-07-01 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
        ]);
        $rebel_only = $this->attended($trooper, $this->shiftAt('2026-07-08 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('501st Legion')->id], $partial->refresh()->costume_organization_ids);
        $this->assertSame([$this->club('501st Legion')->id], $rebel_only->refresh()->costume_organization_ids);
    }

    public function test_leaves_tt1_signups_to_the_legacy_record(): void
    {
        [$trooper] = $this->falseRebelMember(MembershipStatus::RETIRED);
        $shift = $this->shiftAt('2019-02-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Rebel Pilot', 1));
        $event_trooper = $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('Rebel Legion')->id], $event_trooper->refresh()->costume_organization_ids);
    }

    public function test_removes_achievements_tied_to_the_false_club(): void
    {
        [$trooper] = $this->falseRebelMember(MembershipStatus::RETIRED);
        TrooperAchievement::factory()->forTrooper($trooper)->forOrganization($this->club('Rebel Legion'))->create();

        $this->runFix();

        $this->assertSame(0, TrooperAchievement::withTrashed()->count());
    }

    public function test_leaves_an_active_membership_alone_and_reports_it(): void
    {
        Trooper::factory()->asAdministrator()->create();
        [$trooper, $membership, $assignment] = $this->falseRebelMember(MembershipStatus::ACTIVE);
        $event_trooper = $this->attended($trooper, $this->shiftAt('2026-07-01 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertFalse($membership->refresh()->trashed());
        $this->assertTrue((bool) $assignment->refresh()->is_member);
        $this->assertSame([$this->club('Rebel Legion')->id], $event_trooper->refresh()->costume_organization_ids);
        Mail::assertQueued(Fix408AmbiguousMemberships::class, function (Fix408AmbiguousMemberships $mail): bool
        {
            return str_contains($mail->render(), 'Rebel Legion');
        });
    }

    public function test_rerun_makes_no_further_changes(): void
    {
        [$trooper, $membership] = $this->falseRebelMember(MembershipStatus::RETIRED);
        $event_trooper = $this->attended($trooper, $this->shiftAt('2026-07-08 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();
        $first_updated_at = $event_trooper->refresh()->updated_at;
        $first_deleted_at = $membership->refresh()->deleted_at;

        $this->travel(1)->hours();
        $this->runFix();

        $this->assertEquals($first_updated_at, $event_trooper->refresh()->updated_at);
        $this->assertEquals($first_deleted_at, $membership->refresh()->deleted_at);
        $this->assertSame([$this->club('501st Legion')->id], $event_trooper->costume_organization_ids);
    }

    /**
     * A real TT1.0 501st member whose stray Rebel identifier (pRebel = 0) became a membership.
     *
     * @return array{0: Trooper, 1: TrooperOrganization, 2: TrooperAssignment}
     */
    private function falseRebelMember(MembershipStatus $status): array
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'tkid' => "TK{$trooper->id}", 'pRebel' => 0, 'rebelforum' => "stray{$trooper->id}"]);
        $this->memberAssignmentSince($trooper, $this->club('501st Legion'), self::IMPORTED_AT);

        $membership = $this->membershipSince($trooper, $this->club('Rebel Legion'), self::IMPORTED_AT, $status, "stray{$trooper->id}");
        $assignment = $this->memberAssignmentSince($trooper, $this->club('Rebel Legion'), '2026-06-01 00:00:00');

        return [$trooper, $membership, $assignment];
    }

    private function runFix(): void
    {
        (new Fix408)->run(app(MagicBus::class));
    }
}
