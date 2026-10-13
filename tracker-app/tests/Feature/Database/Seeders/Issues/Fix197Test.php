<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\Issues;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Database\Seeders\Issues\Fix197;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class Fix197Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Organization::factory()->asOrganization()->withName('501st Legion')->create();
        $academy = Organization::factory()
            ->asOrganization()
            ->withName('Galactic Academy')
            ->create();
        $campus = Organization::factory()
            ->asRegion()
            ->withParent($academy)
            ->withName('North America Coruscant Campus')
            ->create();
        Organization::factory()
            ->asUnit()
            ->withParent($campus)
            ->withName('Florida Dagobah School')
            ->create();

        // createVisitor() looks the guardian up before createGuardian() runs
        Trooper::factory()->create([Trooper::EMAIL => 'guardian@sw.com']);
    }

    public function test_rerun_restores_a_soft_deleted_membership_instead_of_duplicating_it(): void
    {
        $subject = new Fix197;
        $subject->run();

        $visitor = Trooper::firstWhere(Trooper::EMAIL, 'visitor@sw.com');
        $this->membershipsOf($visitor)->first()->delete();

        $subject->run();

        $memberships = $this->membershipsOf($visitor);
        $this->assertCount(1, $memberships);
        $this->assertFalse($memberships->first()->trashed());
        $this->assertSame(MembershipStatus::PENDING, $memberships->first()->membership_status);
    }

    /** @return Collection<int, TrooperOrganization> */
    private function membershipsOf(Trooper $trooper): Collection
    {
        return TrooperOrganization::withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->get();
    }
}
