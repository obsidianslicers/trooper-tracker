<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see UpdateTrooperCommand
 */
class UpdateTrooperCommandTest extends TestCase
{
    public function test_constructor_stores_trooper_and_valid_data(): void
    {
        $trooper = Trooper::factory()->make([Trooper::ID => 123]);
        $valid_data = ['display_name' => 'New Name', 'phone' => '555-1234'];
        $complete_setup = false;

        $subject = new UpdateTrooperCommand(
            trooper: $trooper,
            valid_data: $valid_data,
            complete_setup: $complete_setup
        );

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($valid_data, $subject->valid_data);
        $this->assertSame($complete_setup, $subject->complete_setup);
    }

    public function test_constructor_defaults_complete_setup_to_false(): void
    {
        $trooper = Trooper::factory()->make();
        $valid_data = ['display_name' => 'Test'];

        $subject = new UpdateTrooperCommand(
            trooper: $trooper,
            valid_data: $valid_data
        );

        $this->assertFalse($subject->complete_setup);
    }
}
