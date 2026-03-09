<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationCostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_organization_costume(): void
    {
        $subject = OrganizationCostume::factory()->create();

        $this->assertInstanceOf(OrganizationCostume::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}