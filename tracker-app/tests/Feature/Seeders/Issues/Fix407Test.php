<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Bus\MagicBus;
use App\Mail\Fix407OutstandingCredit;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Database\Seeders\Issues\Fix407;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Seeders\Issues\Concerns\SeedsLegacyTables;
use Tests\TestCase;

class Fix407Test extends TestCase
{
    use RefreshDatabase;
    use SeedsLegacyTables;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLegacyWorld();
    }

    public function test_replaces_credit_from_current_membership_with_the_legacy_signup_club(): void
    {
        $trooper = $this->legacyMember(['p501' => 1, 'pRebel' => 1]);
        $shift = $this->shiftAt('2019-02-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Stormtrooper', 0));
        $event_trooper = $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('501st Legion')->id], $event_trooper->refresh()->costume_organization_ids);
    }

    public function test_keeps_a_stored_region_under_the_legacy_club(): void
    {
        $region = $this->regionOf('501st Legion');
        $trooper = $this->legacyMember(['p501' => 1]);
        $shift = $this->shiftAt('2019-02-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Stormtrooper', 0));
        $event_trooper = $this->attended($trooper, $shift, [EventTrooper::COSTUME_ORGANIZATION_IDS => [$region->id]]);

        $this->runFix();

        $this->assertSame([$region->id], $event_trooper->refresh()->costume_organization_ids);
    }

    public function test_adds_every_club_on_a_dual_legacy_tag(): void
    {
        $trooper = $this->legacyMember(['p501' => 1]);
        $shift = $this->shiftAt('2019-02-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('N/A', 5));
        $event_trooper = $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('501st Legion')->id],
        ]);

        $this->runFix();

        $this->assertEqualsCanonicalizing(
            [$this->club('501st Legion')->id, $this->club('Rebel Legion')->id],
            $event_trooper->refresh()->costume_organization_ids,
        );
    }

    public function test_reports_and_keeps_credit_when_the_legacy_costume_has_no_club(): void
    {
        Trooper::factory()->asAdministrator()->create();
        $trooper = $this->legacyMember(['p501' => 1]);
        $shift = $this->shiftAt('2019-02-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Other', 4));
        $event_trooper = $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('Rebel Legion')->id], $event_trooper->refresh()->costume_organization_ids);
        Mail::assertQueued(Fix407OutstandingCredit::class);
    }

    public function test_leaves_tt2_signups_alone(): void
    {
        $trooper = $this->legacyMember(['p501' => 1]);
        $event_trooper = $this->attended($trooper, $this->shiftAt('2019-02-01 10:00:00'), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();

        $this->assertSame([$this->club('Rebel Legion')->id], $event_trooper->refresh()->costume_organization_ids);
    }

    public function test_rerun_makes_no_further_changes(): void
    {
        $trooper = $this->legacyMember(['p501' => 1, 'pRebel' => 1]);
        $shift = $this->shiftAt('2019-02-01 10:00:00');
        $this->legacySignup($shift, $trooper, $this->legacyCostume('Stormtrooper', 0));
        $event_trooper = $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => [$this->club('Rebel Legion')->id],
        ]);

        $this->runFix();
        $first_updated_at = $event_trooper->refresh()->updated_at;

        $this->travel(1)->hours();
        $this->runFix();

        $this->assertEquals($first_updated_at, $event_trooper->refresh()->updated_at);
        $this->assertSame([$this->club('501st Legion')->id], $event_trooper->costume_organization_ids);
    }

    /** @param  array<string, mixed>  $flags */
    private function legacyMember(array $flags): Trooper
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, $flags);

        return $trooper;
    }

    private function runFix(): void
    {
        (new Fix407)->run(app(MagicBus::class));
    }
}
