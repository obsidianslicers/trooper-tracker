<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_trooper_organization(): void
    {
        $subject = TrooperOrganization::factory()->create();

        $this->assertInstanceOf(TrooperOrganization::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}