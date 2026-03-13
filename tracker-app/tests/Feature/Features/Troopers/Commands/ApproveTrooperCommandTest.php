<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\ApproveTrooperCommand;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see ApproveTrooperCommand
 */
class ApproveTrooperCommandTest extends TestCase
{
    public function test_constructor_stores_trooper_and_is_approved(): void
    {
        $trooper = Trooper::factory()->make([Trooper::ID => 123]);
        $is_approved = true;

        $subject = new ApproveTrooperCommand(
            trooper: $trooper,
            is_approved: $is_approved
        );

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($is_approved, $subject->is_approved);
    }
}
