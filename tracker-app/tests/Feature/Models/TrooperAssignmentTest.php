<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_trooper_assignment(): void
    {
        $subject = TrooperAssignment::factory()->create();

        $this->assertInstanceOf(TrooperAssignment::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}