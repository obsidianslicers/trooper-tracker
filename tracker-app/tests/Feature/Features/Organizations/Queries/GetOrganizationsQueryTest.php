<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationsQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetOrganizationsQueryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_construct_creates_query_instance(): void
    {
        $subject = new GetOrganizationsQuery();

        $this->assertInstanceOf(GetOrganizationsQuery::class, $subject);
    }
}
