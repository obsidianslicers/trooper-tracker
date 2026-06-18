<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\UnvoidTrooperCommand;
use App\Features\Troopers\Commands\UnvoidTrooperCommandHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UnvoidTrooperCommandHandler
 */
class UnvoidTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_membership_status_to_pending(): void
    {
        $trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::INVALID]);
        $subject = app(UnvoidTrooperCommandHandler::class);

        $subject(new UnvoidTrooperCommand($trooper));

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::PENDING, $trooper->membership_status);
    }
}
