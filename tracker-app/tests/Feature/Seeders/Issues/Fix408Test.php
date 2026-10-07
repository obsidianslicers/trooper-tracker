<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\MembershipStatus;
use App\Mail\Fix408AmbiguousMemberships;
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
        [, $membership, $assignment] = $this->falseRebelMember(MembershipStatus::RETIRED);

        $subject = new Fix408;
        $subject->run(app(MagicBus::class));

        $this->assertTrue($membership->refresh()->trashed());
        $assignment = TrooperAssignment::withTrashed()->find($assignment->id);
        $this->assertFalse((bool) $assignment->is_member);
        Mail::assertNotQueued(Fix408AmbiguousMemberships::class);
    }

    public function test_strips_the_false_club_from_tt2_credit_and_re_resolves_what_is_left(): void
    {
        [$trooper] = $this->falseRebelMember(MembershipStatus::RETIRED);
        $both = $this->clubIds('501st Legion', 'Rebel Legion');
        $rebel = $this->clubIds('Rebel Legion');
        $partial = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', $both);
        $rebel_only = $this->attendedWithCredit($trooper, '2026-07-08 10:00:00', $rebel);

        $subject = new Fix408;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['501st Legion'], $partial);
        $this->assertCredit(['501st Legion'], $rebel_only);
    }

    public function test_leaves_tt1_signups_to_the_legacy_record(): void
    {
        [$trooper] = $this->falseRebelMember(MembershipStatus::RETIRED);
        $rebel = $this->clubIds('Rebel Legion');
        $event_trooper = $this->tt1Row($trooper, '2019-02-01 10:00:00', 'Rebel Pilot', 1, $rebel);

        $subject = new Fix408;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['Rebel Legion'], $event_trooper);
    }

    public function test_removes_achievements_tied_to_the_false_club(): void
    {
        [$trooper] = $this->falseRebelMember(MembershipStatus::RETIRED);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($this->club('Rebel Legion'))
            ->create();

        $subject = new Fix408;
        $subject->run(app(MagicBus::class));

        $this->assertSame(0, TrooperAchievement::withTrashed()->count());
    }

    public function test_leaves_an_active_membership_alone_and_reports_it(): void
    {
        Trooper::factory()->asAdministrator()->create();
        [$trooper, $membership, $assignment] = $this->falseRebelMember(MembershipStatus::ACTIVE);
        $rebel = $this->clubIds('Rebel Legion');
        $event_trooper = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', $rebel);

        $subject = new Fix408;
        $subject->run(app(MagicBus::class));

        $this->assertFalse($membership->refresh()->trashed());
        $this->assertTrue((bool) $assignment->refresh()->is_member);
        $this->assertCredit(['Rebel Legion'], $event_trooper);
        Mail::assertQueued(Fix408AmbiguousMemberships::class, function ($mail): bool
        {
            return str_contains($mail->render(), 'Rebel Legion');
        });
    }

    public function test_rerun_makes_no_further_changes(): void
    {
        [$trooper, $membership] = $this->falseRebelMember(MembershipStatus::RETIRED);
        $rebel = $this->clubIds('Rebel Legion');
        $event_trooper = $this->attendedWithCredit($trooper, '2026-07-08 10:00:00', $rebel);

        $subject = new Fix408;
        $subject->run(app(MagicBus::class));
        $first_updated_at = $event_trooper->refresh()->updated_at;
        $first_deleted_at = $membership->refresh()->deleted_at;

        $this->travel(1)->hours();
        $subject->run(app(MagicBus::class));

        $this->assertEquals($first_updated_at, $event_trooper->refresh()->updated_at);
        $this->assertEquals($first_deleted_at, $membership->refresh()->deleted_at);
        $this->assertCredit(['501st Legion'], $event_trooper);
    }

    /**
     * A real TT1.0 501st member whose stray Rebel identifier (pRebel = 0) became a membership.
     *
     * @return array{0: Trooper, 1: TrooperOrganization, 2: TrooperAssignment}
     */
    private function falseRebelMember(MembershipStatus $status): array
    {
        $trooper = Trooper::factory()->asActive()->create();
        $stray = "stray{$trooper->id}";
        $this->legacyTrooper($trooper, [
            'p501' => 1,
            'tkid' => "TK{$trooper->id}",
            'rebelforum' => $stray,
        ]);
        $this->memberAssignmentSince($trooper, $this->club('501st Legion'), self::IMPORTED_AT);

        $rebel = $this->club('Rebel Legion');
        $membership = $this->membershipSince($trooper, $rebel, self::IMPORTED_AT, $status, $stray);
        $assignment = $this->memberAssignmentSince($trooper, $rebel, '2026-06-01 00:00:00');

        return [$trooper, $membership, $assignment];
    }
}
