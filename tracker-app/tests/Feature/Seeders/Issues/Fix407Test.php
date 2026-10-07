<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues;

use App\Bus\MagicBus;
use App\Mail\Fix407OutstandingCredit;
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

    private const string SHIFT_AT = '2019-02-01 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->seedLegacyWorld();
    }

    public function test_replaces_credit_from_current_membership_with_the_legacy_signup_club(): void
    {
        $trooper = $this->legacyMember(['p501' => 1, 'pRebel' => 1]);
        $credit = $this->clubIds('501st Legion', 'Rebel Legion');
        $event_trooper = $this->tt1Row($trooper, self::SHIFT_AT, 'Stormtrooper', 0, $credit);

        $subject = new Fix407;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['501st Legion'], $event_trooper);
    }

    public function test_keeps_a_stored_region_under_the_legacy_club(): void
    {
        $region = $this->regionOf('501st Legion');
        $trooper = $this->legacyMember(['p501' => 1]);
        $event_trooper = $this->tt1Row($trooper, self::SHIFT_AT, 'Stormtrooper', 0, [$region->id]);

        $subject = new Fix407;
        $subject->run(app(MagicBus::class));

        $this->assertSame([$region->id], $event_trooper->refresh()->costume_organization_ids);
    }

    public function test_adds_every_club_on_a_dual_legacy_tag(): void
    {
        $trooper = $this->legacyMember(['p501' => 1]);
        $credit = $this->clubIds('501st Legion');
        $event_trooper = $this->tt1Row($trooper, self::SHIFT_AT, 'N/A', 5, $credit);

        $subject = new Fix407;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['501st Legion', 'Rebel Legion'], $event_trooper);
    }

    public function test_reports_and_keeps_credit_when_the_legacy_costume_has_no_club(): void
    {
        Trooper::factory()->asAdministrator()->create();
        $trooper = $this->legacyMember(['p501' => 1]);
        $credit = $this->clubIds('Rebel Legion');
        $event_trooper = $this->tt1Row($trooper, self::SHIFT_AT, 'Other', 4, $credit);

        $subject = new Fix407;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['Rebel Legion'], $event_trooper);
        Mail::assertQueued(Fix407OutstandingCredit::class);
    }

    public function test_leaves_tt2_signups_alone(): void
    {
        $trooper = $this->legacyMember(['p501' => 1]);
        $credit = $this->clubIds('Rebel Legion');
        $event_trooper = $this->attendedWithCredit($trooper, self::SHIFT_AT, $credit);

        $subject = new Fix407;
        $subject->run(app(MagicBus::class));

        $this->assertCredit(['Rebel Legion'], $event_trooper);
    }

    public function test_rerun_makes_no_further_changes(): void
    {
        $trooper = $this->legacyMember(['p501' => 1, 'pRebel' => 1]);
        $credit = $this->clubIds('Rebel Legion');
        $event_trooper = $this->tt1Row($trooper, self::SHIFT_AT, 'Stormtrooper', 0, $credit);

        $subject = new Fix407;
        $subject->run(app(MagicBus::class));
        $first_updated_at = $event_trooper->refresh()->updated_at;

        $this->travel(1)->hours();
        $subject->run(app(MagicBus::class));

        $this->assertEquals($first_updated_at, $event_trooper->refresh()->updated_at);
        $this->assertCredit(['501st Legion'], $event_trooper);
    }

    /** @param  array<string, mixed>  $flags */
    private function legacyMember(array $flags): Trooper
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, $flags);

        return $trooper;
    }
}
