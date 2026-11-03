<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetCommandStaff;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCommandStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_active_admins_and_moderators_sorted_by_name(): void
    {
        Trooper::factory()->asAdministrator()->withDisplayName('Zulu Admin')->create();
        Trooper::factory()->asModerator()->withDisplayName('Alpha Moderator')->create();
        Trooper::factory()->asMember()->withDisplayName('Member Trooper')->create();
        Trooper::factory()->asAdministrator()->asPending()->withDisplayName('Pending Admin')->create();

        $result = (new GetCommandStaff)->handle();

        $this->assertSame(['Alpha Moderator', 'Zulu Admin'], $result->pluck('display_name')->all());
    }
}
