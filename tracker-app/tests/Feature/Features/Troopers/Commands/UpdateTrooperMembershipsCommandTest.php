<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see UpdateTrooperMembershipsCommand
 */
class UpdateTrooperMembershipsCommandTest extends TestCase
{
    public function test_constructor_stores_trooper_and_valid_data(): void
    {
        $trooper = Trooper::factory()->make([Trooper::ID => 123]);
        $valid_data = [1 => ['assignment' => 5], 2 => ['assignment' => 10]];

        $subject = new UpdateTrooperMembershipsCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($valid_data, $subject->valid_data);
    }
}
