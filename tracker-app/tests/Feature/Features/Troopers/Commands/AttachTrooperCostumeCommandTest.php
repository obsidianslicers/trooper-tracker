<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\AttachTrooperCostumeCommand;
use App\Models\Trooper;
use Tests\TestCase;

/**
 * @see AttachTrooperCostumeCommand
 */
class AttachTrooperCostumeCommandTest extends TestCase
{
    public function test_constructor_stores_trooper_and_organization_ids(): void
    {
        $trooper = Trooper::factory()->make([Trooper::ID => 123]);
        $organization_ids = [1, 2, 3];

        $subject = new AttachTrooperCostumeCommand(
            trooper: $trooper,
            organization_ids: $organization_ids
        );

        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($organization_ids, $subject->organization_ids);
    }
}
