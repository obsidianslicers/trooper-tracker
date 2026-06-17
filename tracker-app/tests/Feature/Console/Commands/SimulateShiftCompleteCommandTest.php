<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SimulateShiftCompleteCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_outputs_urls_for_trooper_with_membership(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $this->artisan('tracker:simulate-shift-complete', ['trooper_id' => $trooper->id])
            ->assertExitCode(0)
            ->expectsOutputToContain($trooper->display_name)
            ->expectsOutputToContain('Attended')
            ->expectsOutputToContain('Unable to attend')
            ->expectsOutputToContain('within 30-day window');
    }

    public function test_command_fails_for_invalid_trooper_id(): void
    {
        $this->artisan('tracker:simulate-shift-complete', ['trooper_id' => 99999])
            ->assertExitCode(1)
            ->expectsOutputToContain('No trooper found');
    }

    public function test_command_creates_expired_scenario_with_flag(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $this->artisan('tracker:simulate-shift-complete', [
            'trooper_id' => $trooper->id,
            '--expired'  => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('outside 30-day window')
            ->expectsOutputToContain('no');
    }

    public function test_command_fails_dual_costume_without_two_memberships(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $this->artisan('tracker:simulate-shift-complete', [
            'trooper_id'      => $trooper->id,
            '--dual-costume'  => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('at least 2 clubs');
    }

    public function test_command_fails_triple_costume_without_three_memberships(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $this->artisan('tracker:simulate-shift-complete', [
            'trooper_id'       => $trooper->id,
            '--triple-costume' => true,
        ])
            ->assertExitCode(1)
            ->expectsOutputToContain('at least 3 clubs');
    }

    public function test_command_dual_costume_shows_club_selection_info(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create(['name' => 'Florida Garrison']);
        $org2 = Organization::factory()->create(['name' => 'Southern Region']);

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $this->artisan('tracker:simulate-shift-complete', [
            'trooper_id'      => $trooper->id,
            '--dual-costume'  => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Florida Garrison')
            ->expectsOutputToContain('Southern Region')
            ->expectsOutputToContain('Club selection will be shown');

        $event_trooper = EventTrooper::latest('id')->first();

        $this->assertEqualsCanonicalizing([$org1->id, $org2->id], $event_trooper->costume_organization_ids);
        $this->assertSame(
            2,
            OrganizationCostume::where(OrganizationCostume::COSTUME_ID, $event_trooper->costume_id)
                ->whereIn(OrganizationCostume::ORGANIZATION_ID, [$org1->id, $org2->id])
                ->whereHas('trooper_costumes', fn ($query) => $query->where(TrooperCostume::TROOPER_ID, $trooper->id))
                ->count()
        );
    }

    public function test_command_dual_costume_does_not_use_single_club_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create(['name' => 'Florida Garrison']);
        $org2 = Organization::factory()->create(['name' => 'Rebel Legion']);
        $single_club_costume = Costume::factory()->withName('Single Club Costume')->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();

        $single_org_costume = OrganizationCostume::factory()->forOrganization($org1)->forCostume($single_club_costume)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($single_org_costume)->create();

        $this->artisan('tracker:simulate-shift-complete', [
            'trooper_id'      => $trooper->id,
            '--dual-costume'  => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Club selection will be shown');

        $event_trooper = EventTrooper::latest('id')->first();

        $this->assertNotSame($single_club_costume->id, $event_trooper->costume_id);
        $this->assertEqualsCanonicalizing([$org1->id, $org2->id], $event_trooper->costume_organization_ids);
    }

    public function test_command_triple_costume_shows_club_selection_info(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create(['name' => 'Florida Garrison']);
        $org2 = Organization::factory()->create(['name' => 'Rebel Legion']);
        $org3 = Organization::factory()->create(['name' => 'Mandalorian Mercs']);

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org3)->asMember()->create();

        $this->artisan('tracker:simulate-shift-complete', [
            'trooper_id'       => $trooper->id,
            '--triple-costume' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Florida Garrison')
            ->expectsOutputToContain('Rebel Legion')
            ->expectsOutputToContain('Mandalorian Mercs')
            ->expectsOutputToContain('Club selection will be shown');

        $event_trooper = EventTrooper::latest('id')->first();

        $this->assertEqualsCanonicalizing([$org1->id, $org2->id, $org3->id], $event_trooper->costume_organization_ids);
        $this->assertSame(
            3,
            OrganizationCostume::where(OrganizationCostume::COSTUME_ID, $event_trooper->costume_id)
                ->whereIn(OrganizationCostume::ORGANIZATION_ID, [$org1->id, $org2->id, $org3->id])
                ->whereHas('trooper_costumes', fn ($query) => $query->where(TrooperCostume::TROOPER_ID, $trooper->id))
                ->count()
        );
    }

    public function test_command_triple_costume_does_not_use_dual_club_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $org1 = Organization::factory()->create(['name' => 'Florida Garrison']);
        $org2 = Organization::factory()->create(['name' => 'Rebel Legion']);
        $org3 = Organization::factory()->create(['name' => 'Mandalorian Mercs']);
        $dual_club_costume = Costume::factory()->withName('Dual Club Costume')->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org1)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org2)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($org3)->asMember()->create();

        foreach ([$org1, $org2] as $org)
        {
            $org_costume = OrganizationCostume::factory()->forOrganization($org)->forCostume($dual_club_costume)->create();
            TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($org_costume)->create();
        }

        $this->artisan('tracker:simulate-shift-complete', [
            'trooper_id'       => $trooper->id,
            '--triple-costume' => true,
        ])
            ->assertExitCode(0)
            ->expectsOutputToContain('Club selection will be shown');

        $event_trooper = EventTrooper::latest('id')->first();

        $this->assertNotSame($dual_club_costume->id, $event_trooper->costume_id);
        $this->assertEqualsCanonicalizing([$org1->id, $org2->id, $org3->id], $event_trooper->costume_organization_ids);
    }
}
