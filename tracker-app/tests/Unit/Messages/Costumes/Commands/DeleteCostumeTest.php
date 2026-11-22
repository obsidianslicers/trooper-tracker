<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Commands;

use App\Messages\Costumes\Commands\DeleteCostume;
use App\Messages\Costumes\Queries\GetCostumeDeletionImpact;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteCostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_impact_counts_then_delete_resets_signups_and_profiles(): void
    {
        $costume = Costume::factory()->create();
        $link = OrganizationCostume::factory()->forCostume($costume)->create();
        $trooper_costume = TrooperCostume::factory()
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->forOrganizationCostume($link)
            ->create();
        $signup = EventTrooper::factory()->withCostume($costume)->create();

        $impact = (new GetCostumeDeletionImpact($costume))->handle();
        (new DeleteCostume($costume))->handle();

        $this->assertSame(['event_troopers' => 1, 'trooper_costumes' => 1], $impact);
        $this->assertSoftDeleted($costume);
        $this->assertSoftDeleted($link);
        $this->assertSoftDeleted(TrooperCostume::withTrashed()->withPending()->find($trooper_costume->id));
        $this->assertNull($signup->fresh()->costume_id);
    }
}
