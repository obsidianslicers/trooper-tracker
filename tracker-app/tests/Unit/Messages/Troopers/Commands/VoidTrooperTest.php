<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\VoidTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoidTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sets_membership_status_to_void(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        (new VoidTrooper($trooper))->handle();

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::INVALID, $trooper->membership_status);
    }
}
