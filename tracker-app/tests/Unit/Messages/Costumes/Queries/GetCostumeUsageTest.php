<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Queries;

use App\Messages\Costumes\Queries\GetCostumeUsage;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCostumeUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_counts_references_including_soft_deleted(): void
    {
        $costume = Costume::factory()->create();
        $link = OrganizationCostume::factory()
            ->forOrganization(Organization::factory()->asOrganization()->create())
            ->forCostume($costume)
            ->create();
        TrooperCostume::factory()
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->forOrganizationCostume($link)
            ->create();
        TrooperCostume::factory()
            ->forTrooper(Trooper::factory()->asActive()->create())
            ->forOrganizationCostume($link)
            ->create()
            ->delete();
        EventTrooper::factory()->withCostume($costume)->create();
        EventTrooper::factory()->withBackupCostume($costume)->create();
        EventTrooper::factory()->withCostume(Costume::factory()->create())->create();

        $subject = new GetCostumeUsage($costume);

        $this->assertSame(
            ['organizations' => 1, 'troopers' => 2, 'event_signups' => 2],
            $subject->handle(),
        );
    }
}
