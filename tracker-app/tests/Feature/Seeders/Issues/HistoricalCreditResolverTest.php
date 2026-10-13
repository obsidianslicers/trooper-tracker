<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Enums\MembershipStatus;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Carbon\Carbon;
use Database\Seeders\Issues\Support\CreditResolution;
use Database\Seeders\Issues\Support\HistoricalCreditResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seeders\Issues\Concerns\SeedsLegacyTables;
use Tests\TestCase;

class HistoricalCreditResolverTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLegacyTables;

    private const string IMPORTED_AT = '2026-05-29 08:34:05';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLegacyWorld();
    }

    public function test_origin_is_decided_per_signup_not_by_shift_date(): void
    {
        $shift = $this->shiftAt('2026-12-01 10:00:00');

        $tt1_trooper = $this->launchMember501st();
        $this->legacySignup($shift, $tt1_trooper, $this->legacyCostume('Rebel Pilot', 1));
        $tt1_row = $this->attended($tt1_trooper, $shift);

        $tt2_trooper = $this->launchMember501st();
        $tt2_row = $this->attended($tt2_trooper, $shift);
        $old_tt2_row = $this->attended($tt2_trooper, $this->shiftAt('2018-04-01 10:00:00'));

        $subject = HistoricalCreditResolver::load();

        $this->assertTrue($subject->isTt1Signup($tt1_row));
        $this->assertFalse($subject->isTt1Signup($tt2_row));
        $this->assertExpected(['Rebel Legion'], $subject->expectedCredit($tt1_row));
        $this->assertExpected(['501st Legion'], $subject->expectedCredit($tt2_row));
        $this->assertExpected(['501st Legion'], $subject->expectedCredit($old_tt2_row));
    }

    public function test_tt1_signup_ignores_a_club_joined_later(): void
    {
        $trooper = $this->launchMember501st();
        $this->joinRebelLegion($trooper, '2026-08-02 12:00:00');
        $row = $this->tt1Row($trooper, '2017-03-01 10:00:00', 'Handler', 0);

        $subject = HistoricalCreditResolver::load();

        $this->assertSame($this->clubIds('501st Legion'), $subject->expectedCredit($row)->org_ids);
    }

    public function test_tt2_signups_are_only_credited_to_clubs_joined_by_the_shift_date(): void
    {
        $trooper = $this->launchMember501st();
        $this->joinRebelLegion($trooper, '2026-08-02 12:00:00');
        $before = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', null);
        $after = $this->attendedWithCredit($trooper, '2026-09-01 10:00:00', null);

        $subject = HistoricalCreditResolver::load();

        $this->assertSame(
            $this->clubIds('501st Legion'),
            $subject->rootsOf($subject->expectedCredit($before)->org_ids),
        );
        $this->assertEqualsCanonicalizing(
            $this->clubIds('501st Legion', 'Rebel Legion'),
            $subject->rootsOf($subject->expectedCredit($after)->org_ids),
        );
    }

    public function test_launch_membership_does_not_carry_past_a_retirement(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1]);
        $p501 = $this->club('501st Legion');
        $retired = MembershipStatus::RETIRED;
        $membership = $this->membershipSince($trooper, $p501, self::IMPORTED_AT, $retired);
        $membership->forceFill([$membership::UPDATED_AT => '2026-07-01 00:00:00'])->saveQuietly();
        $before = $this->attendedWithCredit($trooper, '2026-06-15 10:00:00', null);
        $after = $this->attendedWithCredit($trooper, '2026-08-01 10:00:00', null);

        $subject = HistoricalCreditResolver::load();

        $this->assertExpected(['501st Legion'], $subject->expectedCredit($before));
        $this->assertFalse($subject->expectedCredit($after)->resolved);
    }

    public function test_launch_membership_with_no_later_evidence_is_unknown_after_launch(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1]);
        $p501 = $this->clubId('501st Legion');

        $subject = HistoricalCreditResolver::load();

        $may = $subject->membershipStatusAt($trooper->id, $p501, Carbon::parse('2026-05'));
        $august = $subject->membershipStatusAt($trooper->id, $p501, Carbon::parse('2026-08'));
        $this->assertSame(HistoricalCreditResolver::MEMBER, $may);
        $this->assertSame(HistoricalCreditResolver::UNKNOWN, $august);
    }

    public function test_a_root_to_region_move_keeps_the_original_join_date(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper);
        $region = $this->regionOf('Rebel Legion');
        $this->membershipSince($trooper, $this->club('Rebel Legion'), '2026-06-10 00:00:00');
        $this->memberAssignmentSince($trooper, $region, '2026-09-01 00:00:00');
        $row = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', null);

        $subject = HistoricalCreditResolver::load();

        $this->assertSame([$region->id], $subject->expectedCredit($row)->org_ids);
    }

    public function test_a_stray_import_row_is_not_a_join_date_for_a_real_later_join(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'pRebel' => 0, 'rebelforum' => 'stray']);
        $this->memberAssignmentSince($trooper, $this->club('501st Legion'), self::IMPORTED_AT);
        // the import's false Rebel row, later reused when the trooper really joined
        $rebel = $this->club('Rebel Legion');
        $active = MembershipStatus::ACTIVE;
        $this->membershipSince($trooper, $rebel, self::IMPORTED_AT, $active, 'RL-1');
        $this->memberAssignmentSince($trooper, $rebel, '2026-08-02 00:00:00');

        $subject = HistoricalCreditResolver::load();

        $july = $subject->membershipStatusAt($trooper->id, $rebel->id, Carbon::parse('2026-07'));
        $sept = $subject->membershipStatusAt($trooper->id, $rebel->id, Carbon::parse('2026-09'));
        $this->assertSame(HistoricalCreditResolver::JOINED_LATER, $july);
        $this->assertSame(HistoricalCreditResolver::MEMBER, $sept);
    }

    public function test_a_flagged_false_import_row_is_never_membership(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['pRebel' => 0, 'rebelforum' => 'stray']);
        $rebel = $this->club('Rebel Legion');
        $retired = MembershipStatus::RETIRED;
        $this->membershipSince($trooper, $rebel, self::IMPORTED_AT, $retired, 'stray');

        $subject = HistoricalCreditResolver::load();

        $status = $subject->membershipStatusAt($trooper->id, $rebel->id, Carbon::parse('2026-07'));
        $this->assertSame(HistoricalCreditResolver::NO_EVIDENCE, $status);
    }

    public function test_single_club_costume_credits_that_club(): void
    {
        $trooper = $this->dualMember();
        $costume = $this->costumeFor(['501st Legion']);

        $result = $this->creditInCostume($trooper, $costume, '2026-09-01 10:00:00');

        $this->assertSame($this->clubIds('501st Legion'), $result->org_ids);
    }

    public function test_multi_club_costume_credits_every_club_the_trooper_was_in(): void
    {
        $trooper = $this->dualMember();
        $costume = $this->costumeFor(['501st Legion', 'Rebel Legion']);

        $result = $this->creditInCostume($trooper, $costume, '2026-09-01 10:00:00');

        $this->assertTrue($result->resolved);
        $this->assertExpected(['501st Legion', 'Rebel Legion'], $result);
    }

    public function test_multi_club_costume_credits_only_the_club_held_on_the_shift_date(): void
    {
        $trooper = $this->launchMember501st();
        $this->joinRebelLegion($trooper, '2026-08-02 12:00:00');
        $costume = $this->costumeFor(['501st Legion', 'Rebel Legion']);

        $result = $this->creditInCostume($trooper, $costume, '2026-07-01 10:00:00');

        $this->assertSame($this->clubIds('501st Legion'), $result->org_ids);
    }

    public function test_costume_without_a_club_is_reported(): void
    {
        $trooper = $this->launchMember501st();
        $costume = Costume::factory()->create([Costume::NAME => 'Mystery Costume']);

        $result = $this->creditInCostume($trooper, $costume, '2026-07-01 10:00:00');

        $this->assertFalse($result->resolved);
        $this->assertStringContainsString('Mystery Costume', $result->reason);
    }

    public function test_check_removes_a_club_joined_after_the_shift(): void
    {
        $trooper = $this->launchMember501st();
        $this->joinRebelLegion($trooper, '2026-08-02 12:00:00');
        $both = $this->clubIds('501st Legion', 'Rebel Legion');
        $row = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', $both);

        $subject = HistoricalCreditResolver::load();
        $check = $subject->checkStoredCredit($row);

        $this->assertSame($this->clubIds('501st Legion'), $check->keep_ids);
        $this->assertSame($this->clubIds('Rebel Legion'), $check->remove_ids);
    }

    public function test_check_keeps_but_flags_credit_with_no_membership_evidence(): void
    {
        $trooper = $this->launchMember501st();
        $saber_guild = $this->clubIds('Saber Guild');
        $row = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', $saber_guild);

        $subject = HistoricalCreditResolver::load();
        $check = $subject->checkStoredCredit($row);

        $this->assertSame($saber_guild, $check->keep_ids);
        $this->assertSame([], $check->remove_ids);
        $this->assertSame($saber_guild, $check->unknown_root_ids);
    }

    private function launchMember501st(): Trooper
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'tkid' => "TK{$trooper->id}"]);
        $this->memberAssignmentSince($trooper, $this->club('501st Legion'), self::IMPORTED_AT);

        return $trooper;
    }

    private function joinRebelLegion(Trooper $trooper, string $joined_at): void
    {
        $this->membershipSince($trooper, $this->club('Rebel Legion'), $joined_at);
        $this->memberAssignmentSince($trooper, $this->club('Rebel Legion'), $joined_at);
    }

    private function dualMember(): Trooper
    {
        $trooper = $this->launchMember501st();
        $this->joinRebelLegion($trooper, '2026-06-01 00:00:00');

        return $trooper;
    }

    /** @param  array<int, string>  $club_names */
    private function costumeFor(array $club_names): Costume
    {
        $costume = Costume::factory()->create();

        foreach ($club_names as $name)
        {
            OrganizationCostume::factory()
                ->forCostume($costume)
                ->forOrganization($this->club($name))
                ->create();
        }

        return $costume;
    }

    /** @param  array<int, string>  $club_names */
    private function assertExpected(array $club_names, CreditResolution $resolution): void
    {
        $this->assertEqualsCanonicalizing($this->clubIds(...$club_names), $resolution->org_ids);
    }

    private function creditInCostume(
        Trooper $trooper,
        Costume $costume,
        string $starts_at,
    ): CreditResolution {
        $row = $this->attended($trooper, $this->shiftAt($starts_at), [
            EventTrooper::COSTUME_ID => $costume->id,
        ]);

        $subject = HistoricalCreditResolver::load();

        return $subject->expectedCredit($row);
    }
}
