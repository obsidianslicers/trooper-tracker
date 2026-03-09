<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\DetachTrooperCostumeCommand;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see DetachTrooperCostumeCommand
 */
class DetachTrooperCostumeCommandTest extends TestCase
{
    public function test_constructor_stores_trooper_and_costume_id(): void
    {
        $trooper = Trooper::factory()->make([Trooper::ID => 123]);
        $costume_id = 456;

        $subject = new DetachTrooperCostumeCommand(
            trooper: $trooper,
            costume_id: $costume_id
        );

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($costume_id, $subject->costume_id);
    }
}
