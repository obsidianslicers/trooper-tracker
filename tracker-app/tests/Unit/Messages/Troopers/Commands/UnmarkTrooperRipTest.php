<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\UnmarkTrooperRip;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnmarkTrooperRipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sets_membership_status_to_pending(): void
    {
        $trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::DEPARTED]);

        (new UnmarkTrooperRip($trooper))->handle();

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::PENDING, $trooper->membership_status);
    }
}
