<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Commands\MarkTrooperRip;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkTrooperRipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_sets_membership_status_to_rip(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        (new MarkTrooperRip($trooper))->handle();

        $trooper->refresh();
        $this->assertEquals(MembershipStatus::DEPARTED, $trooper->membership_status);
    }
}
