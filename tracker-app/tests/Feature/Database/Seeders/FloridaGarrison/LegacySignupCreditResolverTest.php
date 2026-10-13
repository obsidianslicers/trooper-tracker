<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\FloridaGarrison;

use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Database\Seeders\FloridaGarrison\Support\LegacyCredit;
use Database\Seeders\FloridaGarrison\Support\LegacySignupCreditResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Seeders\Issues\Concerns\SeedsLegacyTables;
use Tests\TestCase;

class LegacySignupCreditResolverTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLegacyTables;

    private const string SHIFT_AT = '2018-04-01 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLegacyWorld();
    }

    public function test_resolve_credits_the_signup_costume_club(): void
    {
        $row = $this->signupBy(['p501' => 1], 'Stormtrooper', 0);

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($row['shift_id'], $row['trooper_id']);

        $this->assertTrue($result->isResolved());
        $this->assertSame($this->clubIds('501st Legion'), $result->org_ids);
    }

    public function test_resolve_credits_every_club_on_a_dual_tag(): void
    {
        $row = $this->signupBy(['p501' => 1], 'N/A', 5);

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($row['shift_id'], $row['trooper_id']);

        $both = $this->clubIds('501st Legion', 'Rebel Legion');
        $this->assertEqualsCanonicalizing($both, $result->org_ids);
    }

    public function test_resolve_credits_a_per_club_handler_costume_to_its_club(): void
    {
        $row = $this->signupBy(['p501' => 1, 'pRebel' => 1], 'Handler', 1);

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($row['shift_id'], $row['trooper_id']);

        $this->assertSame($this->clubIds('Rebel Legion'), $result->org_ids);
    }

    public function test_resolve_trusts_the_signup_even_when_the_permission_flag_is_zero(): void
    {
        $row = $this->signupBy(['p501' => 0, 'pRebel' => 1], 'Stormtrooper', 0);

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($row['shift_id'], $row['trooper_id']);

        $this->assertSame($this->clubIds('501st Legion'), $result->org_ids);
    }

    public function test_resolve_reports_an_other_costume_without_guessing_elsewhere(): void
    {
        // a single TT1.0 club and a current membership — neither may be used to guess
        $row = $this->signupBy(['p501' => 1], 'Other', 4);
        TrooperAssignment::factory()
            ->forTrooper(Trooper::find($row['trooper_id']))
            ->forOrganization($this->club('501st Legion'))
            ->asMember()
            ->create();

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($row['shift_id'], $row['trooper_id']);

        $this->assertSame(LegacyCredit::UNMAPPED, $result->status);
        $this->assertSame([], $result->org_ids);
        $this->assertStringContainsString('Other', $result->note);
    }

    public function test_resolve_reports_a_signup_with_no_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1]);
        $shift = $this->shiftAt(self::SHIFT_AT);
        $this->legacySignup($shift, $trooper, 0);

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($shift->id, $trooper->id);

        $this->assertSame(LegacyCredit::UNMAPPED, $result->status);
    }

    public function test_resolve_follows_signups_onto_a_merged_duplicate_shift(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['pRebel' => 1]);
        $shift = $this->shiftAt(self::SHIFT_AT);
        $this->legacyEvent($shift->id, $shift->shift_starts_at);
        // a linked legacy shift starting the same minute is merged into the first one on import
        $this->legacyEvent(90001, $shift->shift_starts_at, $shift->id);
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Rebel Pilot', 1), 90001);

        $subject = LegacySignupCreditResolver::load();

        $this->assertTrue($subject->hasSignup($shift->id, $trooper->id));
        $result = $subject->resolve($shift->id, $trooper->id);
        $this->assertSame($this->clubIds('Rebel Legion'), $result->org_ids);
    }

    public function test_resolve_reports_duplicate_signups_that_disagree(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'pRebel' => 1]);
        $shift = $this->shiftAt(self::SHIFT_AT);
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Stormtrooper', 0));
        $this->legacyEvent(90002, $shift->shift_starts_at, $shift->id);
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Rebel Pilot', 1), 90002);

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($shift->id, $trooper->id);

        $this->assertSame(LegacyCredit::AMBIGUOUS, $result->status);
    }

    public function test_resolve_returns_missing_for_a_tt2_signup(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $shift = $this->shiftAt(self::SHIFT_AT);

        $subject = LegacySignupCreditResolver::load();

        $result = $subject->resolve($shift->id, $trooper->id);
        $this->assertFalse($subject->hasSignup($shift->id, $trooper->id));
        $this->assertSame(LegacyCredit::MISSING, $result->status);
    }

    public function test_launch_member_org_ids_reads_permission_flags(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 3, 'pRebel' => 0, 'rebelforum' => 'stray']);

        $subject = LegacySignupCreditResolver::load();

        $launch_org_ids = $subject->launchMemberOrgIds($trooper->id);
        $this->assertSame($this->clubIds('501st Legion'), $launch_org_ids);
        $stray = $subject->strayIdentifier($trooper->id, $this->clubId('Rebel Legion'));
        $this->assertSame('stray', $stray);
    }

    /**
     * @param  array<string, mixed>  $flags
     * @return array{shift_id: int, trooper_id: int}
     */
    private function signupBy(array $flags, string $costume, int $club): array
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, $flags);
        $shift = $this->shiftAt(self::SHIFT_AT);
        $this->legacySignup($shift, $trooper, $this->legacyCostume($costume, $club));

        return ['shift_id' => $shift->id, 'trooper_id' => $trooper->id];
    }
}
