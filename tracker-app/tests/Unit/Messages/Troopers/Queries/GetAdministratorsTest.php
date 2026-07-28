<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetAdministrators;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAdministratorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_only_active_administrators_sorted_by_display_name(): void
    {
        Trooper::factory()->asAdministrator()->withDisplayName('Zulu Admin')->create();
        Trooper::factory()->asAdministrator()->withDisplayName('Alpha Admin')->create();
        Trooper::factory()->asModerator()->withDisplayName('Moderator Trooper')->create();
        Trooper::factory()->asMember()->withDisplayName('Member Trooper')->create();
        Trooper::factory()->asAdministrator()->asRetired()->withDisplayName('Retired Admin')->create();

        $subject = new GetAdministrators();

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertSame(
            ['Alpha Admin', 'Zulu Admin'],
            $result->pluck(Trooper::DISPLAY_NAME)->all(),
        );
    }
}
