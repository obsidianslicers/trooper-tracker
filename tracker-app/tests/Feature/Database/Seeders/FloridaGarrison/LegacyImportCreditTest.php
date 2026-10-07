<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\FloridaGarrison;

use App\Enums\MembershipRole;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Database\Seeders\FloridaGarrison\EventSeeder;
use Database\Seeders\FloridaGarrison\TrooperOrganizationSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Seeders\Issues\Concerns\SeedsLegacyTables;
use Tests\TestCase;

/**
 * A fresh TT1.0 → TT2.0 import must not create the bad membership/credit data Fix406–409 repair.
 */
class LegacyImportCreditTest extends TestCase
{
    use RefreshDatabase;
    use SeedsLegacyTables;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLegacyWorld();
        $this->createFloridaGarrisonHierarchy();
    }

    public function test_trooper_organization_seeder_ignores_identifiers_without_the_permission_flag(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1, 'tkid' => 'TK1001', 'pRebel' => 0, 'rebelforum' => 'stray']);

        (new TrooperOrganizationSeeder)->run();

        $this->assertTrue(TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $this->club('501st Legion')->id)
            ->exists());
        $this->assertFalse(TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $this->club('Rebel Legion')->id)
            ->exists());
    }

    public function test_trooper_organization_seeder_ignores_a_squad_without_p501(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 0, 'squad' => 1, 'pRebel' => 1, 'rebelforum' => 'RL1']);

        (new TrooperOrganizationSeeder)->run();

        $this->assertFalse(TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereIn(TrooperAssignment::ORGANIZATION_ID, Organization::where(Organization::NAME, 'Everglades Squad')->pluck(Organization::ID))
            ->exists());
    }

    public function test_event_seeder_credits_a_handler_from_the_legacy_costume_club(): void
    {
        $handler = Trooper::factory()->asActive()->create([Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER]);
        $this->legacyTrooper($handler, ['p501' => 1, 'pRebel' => 1]);
        $this->legacyEvent(5001, Carbon::parse('2019-03-02 10:00:00'));
        $this->legacyRawSignup(5001, $handler, $this->legacyCostume('Handler', 1));

        $this->runEventSeeder();

        $this->assertSame([$this->club('Rebel Legion')->id], $this->importedCredit(5001, $handler));
    }

    public function test_event_seeder_leaves_an_unmapped_legacy_costume_uncredited(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->legacyTrooper($trooper, ['p501' => 1]);
        $this->legacyEvent(5002, Carbon::parse('2019-03-09 10:00:00'));
        $this->legacyRawSignup(5002, $trooper, $this->legacyCostume('Other', 4));

        $this->runEventSeeder();

        $this->assertNull($this->importedCredit(5002, $trooper));
    }

    private function createFloridaGarrisonHierarchy(): void
    {
        foreach ($this->clubs as $name => $club)
        {
            if ($name !== '501st Legion')
            {
                $this->regionOf($name);
            }
        }

        $garrison = $this->regionOf('501st Legion', 'Florida Garrison');

        foreach (['Everglades Squad', 'Makaze Squad', 'Parjai Squad', 'Squad 7', 'Tampa Bay Squad'] as $squad)
        {
            Organization::factory()->asUnit()->withParent($garrison)->withName($squad)->create();
        }
    }

    /** db:seed unguards models, which is what lets the import keep legacy ids */
    private function runEventSeeder(): void
    {
        Model::unguarded(fn () => (new EventSeeder)->run());
    }

    private function legacyRawSignup(int $legacy_shift_id, Trooper $trooper, int $costume_id): void
    {
        DB::table('event_sign_up')->insert([
            'trooperid' => $trooper->id,
            'troopid' => $legacy_shift_id,
            'costume' => $costume_id,
            'status' => 3,
            'signuptime' => '2019-01-01 00:00:00',
        ]);
    }

    /** @return array<int, int>|null */
    private function importedCredit(int $shift_id, Trooper $trooper): ?array
    {
        return EventTrooper::query()
            ->where(EventTrooper::EVENT_SHIFT_ID, $shift_id)
            ->where(EventTrooper::TROOPER_ID, $trooper->id)
            ->firstOrFail()
            ->costume_organization_ids;
    }
}
