<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\RecalculateTrooperRankCommand;
use Tests\TestCase;

/**
 * @see RecalculateTrooperRankCommand
 */
class RecalculateTrooperRankCommandTest extends TestCase
{
    public function test_constructor_stores_trooper_id(): void
    {
        $trooper_id = 123;

        $subject = new RecalculateTrooperRankCommand(trooper_id: $trooper_id);

        $this->assertSame($trooper_id, $subject->trooper_id);
    }

    public function test_constructor_defaults_trooper_id_to_null(): void
    {
        $subject = new RecalculateTrooperRankCommand();

        $this->assertNull($subject->trooper_id);
    }
}
