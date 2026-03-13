<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperCostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_trooper_costume(): void
    {
        $subject = TrooperCostume::factory()->create();

        $this->assertInstanceOf(TrooperCostume::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}