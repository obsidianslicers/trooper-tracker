<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\UnvoidTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnvoidTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sets_membership_status_to_pending(): void
    {
        $trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::INVALID]);

        (new UnvoidTrooper($trooper))->handle();

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::PENDING, $trooper->membership_status);
    }
}
