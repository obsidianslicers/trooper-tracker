<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use Tests\TestCase;

class GetTroopersByRoleQueryTest extends TestCase
{
    public function test_construct_with_no_parameters(): void
    {
        // Act
        $subject = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);

        // Assert
        $this->assertInstanceOf(GetTroopersByRoleQuery::class, $subject);
    }
}
