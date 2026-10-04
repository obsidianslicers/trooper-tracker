<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetModerators;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetModeratorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_active_moderators_sorted_by_display_name(): void
    {
        Trooper::factory()->asModerator()->withDisplayName('Zulu Moderator')->create();
        Trooper::factory()->asModerator()->withDisplayName('Alpha Moderator')->create();
        Trooper::factory()->asAdministrator()->withDisplayName('Administrator Trooper')->create();
        Trooper::factory()->asMember()->withDisplayName('Member Trooper')->create();
        Trooper::factory()->asModerator()->asRetired()->withDisplayName('Retired Moderator')->create();

        $subject = new GetModerators();

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertSame(
            ['Alpha Moderator', 'Zulu Moderator'],
            $result->pluck(Trooper::DISPLAY_NAME)->all(),
        );
    }
}