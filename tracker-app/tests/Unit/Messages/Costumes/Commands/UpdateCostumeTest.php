<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Commands;

use App\Messages\Costumes\Commands\UpdateCostume;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_renames_and_sets_the_clubs_that_offer_it(): void
    {
        $costume = Costume::factory()->create();
        $kept = Organization::factory()->asOrganization()->create();
        $dropped = Organization::factory()->asOrganization()->create();
        $added = Organization::factory()->asOrganization()->create();
        OrganizationCostume::factory()->forOrganization($kept)->forCostume($costume)->create();
        OrganizationCostume::factory()->forOrganization($dropped)->forCostume($costume)->create();

        (new UpdateCostume($costume, 'TK Mk II', [$kept->id, $added->id]))->handle();

        $this->assertSame('TK Mk II', $costume->fresh()->name);
        $this->assertEqualsCanonicalizing(
            [$kept->id, $added->id],
            OrganizationCostume::query()
                ->where(OrganizationCostume::COSTUME_ID, $costume->id)
                ->pluck(OrganizationCostume::ORGANIZATION_ID)
                ->all()
        );
    }

    public function test_handle_brings_back_a_club_that_was_taken_off(): void
    {
        $costume = Costume::factory()->create();
        $club = Organization::factory()->asOrganization()->create();
        $link = OrganizationCostume::factory()->forOrganization($club)->forCostume($costume)->create();
        $link->delete();

        (new UpdateCostume($costume, $costume->name, [$club->id]))->handle();

        $this->assertNotSoftDeleted($link);
        $this->assertSame(1, OrganizationCostume::withTrashed()->where(OrganizationCostume::COSTUME_ID, $costume->id)->count());
    }
}
