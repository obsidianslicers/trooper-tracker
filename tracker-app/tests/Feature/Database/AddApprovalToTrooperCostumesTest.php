<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddApprovalToTrooperCostumesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject()->down();
    }

    public function test_up_approves_every_existing_costume(): void
    {
        $synced_club = Organization::factory()->create();
        DB::table('tt_organizations')->where('id', $synced_club->id)->update(['synchronizer' => 'legion']);
        $local_club = Organization::factory()->create();

        $trooper = Trooper::factory()->create();
        $from_api = $this->insertCostume($trooper, $synced_club, 'Stormtrooper', now()->subDay());
        $self_added_synced = $this->insertCostume($trooper, $synced_club, 'Emperor Palpatine', null);
        $self_added_local = $this->insertCostume($trooper, $local_club, 'Jedi', null);

        $this->subject()->up();

        $this->assertNotNull($this->approvedAt($from_api));
        $this->assertNotNull($this->approvedAt($self_added_synced));
        $this->assertNotNull($this->approvedAt($self_added_local));
    }

    private function insertCostume(Trooper $trooper, Organization $club, string $name, mixed $synchronized_at): int
    {
        $costume = Costume::factory()->create([Costume::NAME => $name]);
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($club)
            ->forCostume($costume)
            ->create();

        return DB::table('tt_trooper_costumes')->insertGetId([
            'trooper_id' => $trooper->id,
            'organization_costume_id' => $organization_costume->id,
            'synchronized_at' => $synchronized_at,
            'created_at' => now()->subWeek(),
            'updated_at' => now()->subWeek(),
        ]);
    }

    private function approvedAt(int $id): mixed
    {
        return DB::table('tt_trooper_costumes')->where('id', $id)->value('approved_at');
    }

    private function subject(): Migration
    {
        return require database_path(
            'migrations/2026_11_06_000003_add_approval_to_trooper_costumes_table.php'
        );
    }
}
