<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Organizations\Queries;

use App\Features\Organizations\Queries\GetOrganizationsQuery;
use App\Features\Organizations\Queries\GetOrganizationsQueryHandler;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetOrganizationsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_only_organization_type_sorted_by_name(): void
    {
        Organization::factory()->asOrganization()->withName('Zeta Organization')->create();
        Organization::factory()->asOrganization()->withName('Alpha Organization')->create();
        Organization::factory()->asRegion()->withName('Ignored Region')->create();

        $subject = new GetOrganizationsQueryHandler();

        $result = $subject(new GetOrganizationsQuery());

        $this->assertSame(
            ['Alpha Organization', 'Zeta Organization'],
            $result->pluck(Organization::NAME)->all()
        );
        $this->assertCount(2, $result);
    }
}
