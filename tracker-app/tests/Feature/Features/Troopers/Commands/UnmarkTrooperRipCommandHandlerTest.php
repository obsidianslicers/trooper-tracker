<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\UnmarkTrooperRipCommand;
use App\Features\Troopers\Commands\UnmarkTrooperRipCommandHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see UnmarkTrooperRipCommandHandler
 */
class UnmarkTrooperRipCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_membership_status_to_pending(): void
    {
        $trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::RIP]);
        $subject = app(UnmarkTrooperRipCommandHandler::class);

        $subject(new UnmarkTrooperRipCommand($trooper));

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::PENDING, $trooper->membership_status);
    }
}
