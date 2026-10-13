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

    private const string TT1_SHIFT_AT = '2018-06-01 10:00:00';

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
        $both = $this->clubIds('501st Legion', 'Rebel Legion');
        $event_trooper = $this->tt1Row($trooper, self::TT1_SHIFT_AT, 'Handler', 0, $both);

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['501st Legion'], $event_trooper);
    }

    public function test_re_resolves_a_tt1_row_whose_only_credit_was_impossible(): void
    {
        $trooper = $this->laterRebelJoiner();
        $rebel = $this->clubIds('Rebel Legion');
        $event_trooper = $this->tt1Row($trooper, self::TT1_SHIFT_AT, 'Handler', 0, $rebel);

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['501st Legion'], $event_trooper);
    }

    public function test_removes_tt2_credit_for_a_club_joined_after_the_shift(): void
    {
        $trooper = $this->laterRebelJoiner();
        $both = $this->clubIds('501st Legion', 'Rebel Legion');
        $before = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', $both);
        $after = $this->attendedWithCredit($trooper, '2026-09-01 10:00:00', $both);

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['501st Legion'], $before);
        $this->assertCredit(['501st Legion', 'Rebel Legion'], $after);
    }

    public function test_keeps_and_reports_credit_with_no_membership_evidence(): void
    {
        Trooper::factory()->asAdministrator()->create();
        $trooper = $this->laterRebelJoiner();
        $saber_guild = $this->clubIds('Saber Guild');
        $event_trooper = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', $saber_guild);

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['Saber Guild'], $event_trooper);
        Mail::assertQueued(Fix409OutstandingCredit::class, function ($mail): bool
        {
            return str_contains($mail->render(), 'Saber Guild');
        });
    }

    public function test_reports_but_does_not_change_impossible_organization_id_credit(): void
    {
        Trooper::factory()->asAdministrator()->create();
        $trooper = $this->laterRebelJoiner();
        $event_trooper = $this->organizationIdRow($trooper, 'Rebel Legion');

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));

        $event_trooper->refresh();
        $this->assertSame($this->clubId('Rebel Legion'), $event_trooper->organization_id);
        Mail::assertQueued(Fix409OutstandingCredit::class);
    }

    public function test_removes_club_achievements_no_longer_backed_by_credit(): void
    {
        $trooper = $this->laterRebelJoiner();
        $both = $this->clubIds('501st Legion', 'Rebel Legion');
        $this->tt1Row($trooper, self::TT1_SHIFT_AT, 'Handler', 0, $both);
        $kept = $this->firstTroopMilestone($trooper, '501st Legion');
        $removed = $this->firstTroopMilestone($trooper, 'Rebel Legion');

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));

        $this->assertNotNull(TrooperAchievement::find($kept->id));
        $this->assertNull(TrooperAchievement::withTrashed()->find($removed->id));
    }

    public function test_keeps_a_milestone_backed_only_by_organization_id_credit(): void
    {
        $trooper = $this->laterRebelJoiner();
        $this->organizationIdRow($trooper, '501st Legion');
        $milestone = $this->firstTroopMilestone($trooper, '501st Legion');

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));

        $this->assertNotNull(TrooperAchievement::find($milestone->id));
    }

    public function test_rerun_makes_no_further_changes(): void
    {
        $trooper = $this->laterRebelJoiner();
        $both = $this->clubIds('501st Legion', 'Rebel Legion');
        $event_trooper = $this->attendedWithCredit($trooper, '2026-07-01 10:00:00', $both);

        $subject = new Fix409;
        $subject->run(app(MagicBus::class));
        $first_updated_at = $event_trooper->refresh()->updated_at;

        $this->travel(1)->hours();
        $subject->run(app(MagicBus::class));

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

    private function organizationIdRow(Trooper $trooper, string $club_name): EventTrooper
    {
        return $this->attended($trooper, $this->shiftAt('2026-07-01 10:00:00'), [
            EventTrooper::ORGANIZATION_ID => $this->clubId($club_name),
        ]);
    }

    private function firstTroopMilestone(Trooper $trooper, string $club_name): TrooperAchievement
    {
        return TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($this->club($club_name))
            ->withType(AchievementType::FIRST_TROOP)
            ->create();
    }
}
