<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetCommandStaffQuery;
use App\Features\Troopers\Queries\GetCommandStaffQueryHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCommandStaffQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_only_active_admins_and_moderators_sorted_by_name(): void
    {
        Trooper::factory()->asAdministrator()->withDisplayName('Zulu Admin')->create();
        Trooper::factory()->asModerator()->withDisplayName('Alpha Moderator')->create();
        Trooper::factory()->asMember()->withDisplayName('Member Trooper')->create();
        Trooper::factory()->asAdministrator()->asPending()->withDisplayName('Pending Admin')->create();

        $subject = new GetCommandStaffQueryHandler();

        $result = $subject(new GetCommandStaffQuery());

        $this->assertSame(['Alpha Moderator', 'Zulu Admin'], $result->pluck('display_name')->all());
    }
}
