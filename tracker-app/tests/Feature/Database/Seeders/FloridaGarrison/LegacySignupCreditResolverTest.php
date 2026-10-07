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

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLegacyWorld();
    }

    public function test_resolve_credits_the_signup_costume_club(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1]);
        $shift = $this->shiftAt('2018-04-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Stormtrooper', 0));

        $subject = LegacySignupCreditResolver::load();
        $result = $subject->resolve($shift->id, $trooper->id);

        $this->assertTrue($result->isResolved());
        $this->assertSame([$this->club('501st Legion')->id], $result->org_ids);
    }

    public function test_resolve_credits_every_club_on_a_dual_tag(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1]);
        $shift = $this->shiftAt('2018-04-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('N/A', 5));

        $result = LegacySignupCreditResolver::load()->resolve($shift->id, $trooper->id);

        $this->assertEqualsCanonicalizing(
            [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
            $result->org_ids,
        );
    }

    public function test_resolve_credits_a_per_club_handler_costume_to_its_club(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'pRebel' => 1]);
        $shift = $this->shiftAt('2019-04-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Handler', 1));

        $result = LegacySignupCreditResolver::load()->resolve($shift->id, $trooper->id);

        $this->assertSame([$this->club('Rebel Legion')->id], $result->org_ids);
    }

    public function test_resolve_trusts_the_signup_even_when_the_permission_flag_is_zero(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 0, 'pRebel' => 1]);
        $shift = $this->shiftAt('2015-04-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Stormtrooper', 0));

        $result = LegacySignupCreditResolver::load()->resolve($shift->id, $trooper->id);

        $this->assertSame([$this->club('501st Legion')->id], $result->org_ids);
    }

    public function test_resolve_reports_an_other_costume_without_falling_back_to_any_other_source(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        // a single TT1.0 club and a current membership — neither may be used to guess
        $this->legacyTrooper($trooper, ['p501' => 1]);
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($this->club('501st Legion'))->asMember()->create();
        $shift = $this->shiftAt('2018-04-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Other', 4));

        $result = LegacySignupCreditResolver::load()->resolve($shift->id, $trooper->id);

        $this->assertSame(LegacyCredit::UNMAPPED, $result->status);
        $this->assertSame([], $result->org_ids);
        $this->assertStringContainsString('Other', $result->note);
    }

    public function test_resolve_reports_a_signup_with_no_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1]);
        $shift = $this->shiftAt('2018-04-01 10:00:00');
        $this->legacySignup($shift, $trooper, 0);

        $result = LegacySignupCreditResolver::load()->resolve($shift->id, $trooper->id);

        $this->assertSame(LegacyCredit::UNMAPPED, $result->status);
    }

    public function test_resolve_follows_signups_onto_a_merged_duplicate_shift(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['pRebel' => 1]);
        $shift = $this->shiftAt('2018-04-01 10:00:00');
        $this->legacyEvent($shift->id, $shift->shift_starts_at);
        // a linked legacy shift starting the same minute is merged into the first one on import
        $this->legacyEvent(90001, $shift->shift_starts_at, $shift->id);
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Rebel Pilot', 1), 90001);

        $subject = LegacySignupCreditResolver::load();

        $this->assertTrue($subject->hasSignup($shift->id, $trooper->id));
        $this->assertSame([$this->club('Rebel Legion')->id], $subject->resolve($shift->id, $trooper->id)->org_ids);
    }

    public function test_resolve_reports_duplicate_signups_that_disagree(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'pRebel' => 1]);
        $shift = $this->shiftAt('2018-04-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Stormtrooper', 0));
        $this->legacyEvent(90002, $shift->shift_starts_at, $shift->id);
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Rebel Pilot', 1), 90002);

        $result = LegacySignupCreditResolver::load()->resolve($shift->id, $trooper->id);

        $this->assertSame(LegacyCredit::AMBIGUOUS, $result->status);
    }

    public function test_resolve_returns_missing_for_a_tt2_signup(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $shift = $this->shiftAt('2018-04-01 10:00:00');

        $subject = LegacySignupCreditResolver::load();

        $this->assertFalse($subject->hasSignup($shift->id, $trooper->id));
        $this->assertSame(LegacyCredit::MISSING, $subject->resolve($shift->id, $trooper->id)->status);
    }

    public function test_launch_member_org_ids_reads_permission_flags(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 3, 'pRebel' => 0, 'rebelforum' => 'stray']);

        $subject = LegacySignupCreditResolver::load();

        $this->assertSame([$this->club('501st Legion')->id], $subject->launchMemberOrgIds($trooper->id));
        $this->assertSame('stray', $subject->strayIdentifier($trooper->id, $this->club('Rebel Legion')->id));
    }
}
