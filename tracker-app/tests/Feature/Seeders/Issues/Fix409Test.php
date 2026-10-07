<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Bus\MagicBus;
use App\Enums\AchievementType;
use App\Mail\Fix409OutstandingCredit;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Database\Seeders\Issues\Fix409;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Seeders\Issues\Concerns\SeedsLegacyTables;
use Tests\TestCase;

class Fix409Test extends TestCase
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

    public function test_removes_tt1_credit_the_legacy_signup_never_recorded(): void
    {
        // trooper 644: old shifts back-filled to a club joined years later
        $trooper = $this->laterRebelJoiner();
        $shift = $this->shiftAt('2018-06-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Handler', 0));
        $event_trooper = $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('501st Legion')->id], $event_trooper->refresh()->costume_organization_ids);
    }

    public function test_re_resolves_a_tt1_row_whose_only_credit_was_impossible(): void
    {
        $trooper = $this->laterRebelJoiner();
        $shift = $this->shiftAt('2018-06-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Handler', 0));
        $event_trooper = $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('501st Legion')->id], $event_trooper->refresh()->costume_organization_ids);
    }

    public function test_removes_tt2_credit_for_a_club_joined_after_the_shift(): void
    {
        $trooper = $this->laterRebelJoiner();
        $before = $this->attended($trooper, $this->shiftAt('2026-07-01 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
        ]);
        $after = $this->attended($trooper, $this->shiftAt('2026-09-01 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('501st Legion')->id], $before->refresh()->costume_organization_ids);
        $this->assertEqualsCanonicalizing(
            [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
            $after->refresh()->costume_organization_ids,
        );
    }

    public function test_keeps_and_reports_credit_with_no_membership_evidence(): void
    {
        Trooper::factory()->asAdministrator()->create();
        $trooper = $this->laterRebelJoiner();
        $event_trooper = $this->attended($trooper, $this->shiftAt('2026-07-01 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Saber Guild')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('Saber Guild')->id], $event_trooper->refresh()->costume_organization_ids);
        Mail::assertQueued(Fix409OutstandingCredit::class, function (Fix409OutstandingCredit $mail): bool
        {
            return str_contains($mail->render(), 'Saber Guild');
        });
    }

    public function test_reports_but_does_not_change_impossible_organization_id_credit(): void
    {
        Trooper::factory()->asAdministrator()->create();
        $trooper = $this->laterRebelJoiner();
        $event_trooper = $this->attended($trooper, $this->shiftAt('2026-07-01 10:00:00'), [
            EventTrooper::ORGANIZATION_ID => $this->club('Rebel Legion')->id,
        ]);

        $this->runFix();

        $this->assertSame($this->club('Rebel Legion')->id, $event_trooper->refresh()->organization_id);
        Mail::assertQueued(Fix409OutstandingCredit::class);
    }

    public function test_removes_club_achievements_no_longer_backed_by_credit(): void
    {
        $trooper = $this->laterRebelJoiner();
        $shift = $this->shiftAt('2018-06-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Handler', 0));
        $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
        ]);
        $kept = TrooperAchievement::factory()->forTrooper($trooper)->forOrganization($this->club('501st Legion'))
            ->withType(AchievementType::FIRST_TROOP)->create();
        $removed = TrooperAchievement::factory()->forTrooper($trooper)->forOrganization($this->club('Rebel Legion'))
            ->withType(AchievementType::FIRST_TROOP)->create();

        $this->runFix();

        $this->assertNotNull(TrooperAchievement::find($kept->id));
        $this->assertNull(TrooperAchievement::withTrashed()->find($removed->id));
    }

    public function test_rerun_makes_no_further_changes(): void
    {
        $trooper = $this->laterRebelJoiner();
        $event_trooper = $this->attended($trooper, $this->shiftAt('2026-07-01 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
        ]);

        $this->runFix();
        $first_updated_at = $event_trooper->refresh()->updated_at;

        $this->travel(1)->hours();
        $this->runFix();

        $this->assertEquals($first_updated_at, $event_trooper->refresh()->updated_at);
    }

    private function laterRebelJoiner(): Trooper
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'tkid' => "TK{$trooper->id}"]);
        $this->memberAssignmentSince($trooper, $this->club('501st Legion'), self::IMPORTED_AT);
        $this->membershipSince($trooper, $this->club('Rebel Legion'), '2026-08-02 12:00:00');
        $this->memberAssignmentSince($trooper, $this->club('Rebel Legion'), '2026-08-02 12:00:00');

        return $trooper;
    }

    private function runFix(): void
    {
        (new Fix409)->run(app(MagicBus::class));
    }
}
