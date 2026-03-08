<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperApprovalsQuery;
use App\Features\Troopers\Queries\GetTrooperApprovalsQueryHandler;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperApprovalsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_pending_troopers_moderated_by_administrator(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        $pending_alpha = Trooper::factory()->asPending()->withDisplayName('Alpha Pending')->create();
        $pending_zeta = Trooper::factory()->asPending()->withDisplayName('Zeta Pending')->create();
        Trooper::factory()->asMember()->withDisplayName('Active Member')->create();

        $subject = new GetTrooperApprovalsQueryHandler();

        $result = $subject(new GetTrooperApprovalsQuery($admin));

        $this->assertSame(['Alpha Pending', 'Zeta Pending'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_filters_pending_troopers_by_moderated_organizations(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $moderated_org = Organization::factory()->asOrganization()->withNodePath('100.')->create();
        $outside_org = Organization::factory()->asOrganization()->withNodePath('900.')->create();

        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($moderated_org)
            ->asModerator()
            ->create();

        $pending_inside = Trooper::factory()->asPending()->withDisplayName('Inside Pending')->create();
        $pending_outside = Trooper::factory()->asPending()->withDisplayName('Outside Pending')->create();

        TrooperAssignment::factory()
            ->forTrooper($pending_inside)
            ->forOrganization($moderated_org)
            ->asMember()
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($pending_outside)
            ->forOrganization($outside_org)
            ->asMember()
            ->create();

        $subject = new GetTrooperApprovalsQueryHandler();

        $result = $subject(new GetTrooperApprovalsQuery($moderator));

        $this->assertCount(1, $result);
        $this->assertSame('Inside Pending', $result->first()->display_name);
    }
}
