<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationHierarchyQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GetOrganizationHierarchyQueryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_construct_defaults_organization_id_to_null(): void
    {
        $subject = new GetOrganizationHierarchyQuery();

        $this->assertNull($subject->organization_id);
    }

    public function test_construct_sets_organization_id_when_provided(): void
    {
        $subject = new GetOrganizationHierarchyQuery(321);

        $this->assertSame(321, $subject->organization_id);
    }
}
