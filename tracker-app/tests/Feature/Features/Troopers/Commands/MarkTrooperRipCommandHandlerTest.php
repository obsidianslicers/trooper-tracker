<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Commands\MarkTrooperRipCommand;
use App\Features\Troopers\Commands\MarkTrooperRipCommandHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see MarkTrooperRipCommandHandler
 */
class MarkTrooperRipCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_membership_status_to_rip(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $subject = app(MarkTrooperRipCommandHandler::class);

        $subject(new MarkTrooperRipCommand($trooper));

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::DEPARTED, $trooper->membership_status);
    }
}
