<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\AwardTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_award_trooper(): void
    {
        $subject = AwardTrooper::factory()->create();

        $this->assertInstanceOf(AwardTrooper::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}