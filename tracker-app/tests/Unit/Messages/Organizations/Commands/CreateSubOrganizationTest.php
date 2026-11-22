<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Commands;

use App\Enums\OrganizationType;
use App\Messages\Organizations\Commands\CreateSubOrganization;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CreateSubOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_adds_a_region_under_a_club(): void
    {
        $club = Organization::factory()->asOrganization()->create();

        $subject = new CreateSubOrganization($club, 'Florida Garrison');
        $region = $subject->handle();

        $this->assertSame(OrganizationType::REGION, $region->type);
        $this->assertSame($club->id, $region->parent_id);
        $this->assertSame('Florida Garrison', $region->fresh()->name);
    }

    public function test_handle_adds_a_unit_under_a_region(): void
    {
        $region = Organization::factory()->asRegion()->create();

        $subject = new CreateSubOrganization($region, 'Squad');

        $this->assertSame(OrganizationType::UNIT, $subject->handle()->type);
    }

    public function test_handle_refuses_a_child_under_a_unit(): void
    {
        $unit = Organization::factory()->asUnit()->create();

        $this->expectException(InvalidArgumentException::class);

        (new CreateSubOrganization($unit, 'Too Deep'))->handle();
    }
}
