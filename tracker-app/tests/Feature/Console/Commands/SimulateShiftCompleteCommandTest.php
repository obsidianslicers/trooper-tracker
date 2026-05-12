<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
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
    }
}
