<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Enums\MembershipRole;
use Tests\TestCase;

class GetTroopersByRoleQueryTest extends TestCase
{
    public function test_construct_stores_membership_role(): void
    {
        $subject = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);

        $this->assertSame(MembershipRole::ADMINISTRATOR, $subject->membership_role);
    }
}
