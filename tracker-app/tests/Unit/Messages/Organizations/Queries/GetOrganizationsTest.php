<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Queries;

use App\Messages\Organizations\Queries\GetOrganizations;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_top_level_organizations(): void
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->create();
        $region = Organization::factory()
            ->asRegion()
            ->withParent($organization)
            ->withName('Garrison')
            ->create();
        Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withName('Squad')
            ->create();

        $subject = new GetOrganizations();

        $result = $subject->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(
            [$organization->{Organization::ID}],
            $result->modelKeys(),
        );
    }
}
