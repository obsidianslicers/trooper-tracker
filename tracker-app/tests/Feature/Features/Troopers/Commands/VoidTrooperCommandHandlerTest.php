<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\VoidTrooperCommand;
use App\Features\Troopers\Commands\VoidTrooperCommandHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see VoidTrooperCommandHandler
 */
class VoidTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_membership_status_to_void(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $subject = app(VoidTrooperCommandHandler::class);

        $subject(new VoidTrooperCommand($trooper));

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::INVALID, $trooper->membership_status);
    }
}
