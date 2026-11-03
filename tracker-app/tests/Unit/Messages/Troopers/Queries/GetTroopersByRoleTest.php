<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTroopersByRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_active_troopers_with_matching_role_sorted_by_name(): void
    {
        Trooper::factory()->asAdministrator()->withDisplayName('Zeta Admin')->create();
        Trooper::factory()->asAdministrator()->withDisplayName('Alpha Admin')->create();
        Trooper::factory()->asModerator()->withDisplayName('Bravo Moderator')->create();

        $result = (new GetTroopersByRole(MembershipRole::ADMINISTRATOR))->handle();

        $this->assertSame(['Alpha Admin', 'Zeta Admin'], $result->pluck(Trooper::DISPLAY_NAME)->all());
    }

    public function test_handle_excludes_inactive_troopers(): void
    {
        Trooper::factory()->asAdministrator()->asPending()->withDisplayName('Pending Admin')->create();
        Trooper::factory()->asAdministrator()->withDisplayName('Active Admin')->create();

        $result = (new GetTroopersByRole(MembershipRole::ADMINISTRATOR))->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Active Admin', $result->first()->display_name);
    }
}
