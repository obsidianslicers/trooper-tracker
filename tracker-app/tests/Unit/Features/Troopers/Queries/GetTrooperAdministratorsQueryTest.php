<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTrooperAdministratorsQuery;
use Tests\TestCase;

class GetTrooperAdministratorsQueryTest extends TestCase
{
    public function test_construct_with_no_parameters(): void
    {
        // Act
        $subject = new GetTrooperAdministratorsQuery();

        // Assert
        $this->assertInstanceOf(GetTrooperAdministratorsQuery::class, $subject);
    }
}
