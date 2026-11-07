<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Costumes;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumes;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class GetTrooperCostumesTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    public function test_handle_lists_pending_costumes_with_their_pending_clubs(): void
    {
        $this->setUpClubs();
        $trooper = $this->memberOf($this->club);
        $this->memberOf($this->other_club, $trooper);
        foreach ([$this->club, $this->other_club] as $club)
        {
            TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($club)
                ->withMembershipStatus(MembershipStatus::ACTIVE)->create();
        }
        $this->costumeFor($trooper, $this->other_club, pending: false);
        $this->costumeFor($trooper, $this->club);

        $subject = new GetTrooperCostumes($trooper);
        $result = $subject->handle();

        $palpatine = $result->firstWhere('name', 'Emperor Palpatine');
        $this->assertNotNull($palpatine);
        $this->assertSame(['501st Legion'], $palpatine->pending_organizations);
        $this->assertStringContainsString('Rebel Legion', $palpatine->costume_organizations);
    }
}
