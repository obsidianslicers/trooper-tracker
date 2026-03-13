<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Features\Troopers\Queries\GetTroopersByRoleQueryHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersByRoleQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_active_troopers_with_matching_role_sorted_by_name(): void
    {
        Trooper::factory()->asAdministrator()->withDisplayName('Zeta Admin')->create();
        Trooper::factory()->asAdministrator()->withDisplayName('Alpha Admin')->create();
        Trooper::factory()->asModerator()->withDisplayName('Bravo Moderator')->create();

        $subject = new GetTroopersByRoleQueryHandler();

        $result = $subject(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        $this->assertSame(['Alpha Admin', 'Zeta Admin'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_invoke_excludes_inactive_troopers(): void
    {
        Trooper::factory()->asAdministrator()->asPending()->withDisplayName('Pending Admin')->create();
        Trooper::factory()->asAdministrator()->withDisplayName('Active Admin')->create();

        $subject = new GetTroopersByRoleQueryHandler();

        $result = $subject(new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR));

        $this->assertCount(1, $result);
        $this->assertSame('Active Admin', $result->first()->display_name);
    }
}
