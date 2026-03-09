<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Shares;

use App\Models\Event;
use App\Models\EventShare;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareEventRosterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_shared_roster_and_increments_view_count(): void
    {
        $event = Event::factory()->create();
        $trooper = Trooper::factory()->create();
        $share = EventShare::factory()
            ->forEvent($event)
            ->forTrooper($trooper)
            ->create([EventShare::VIEW_COUNT => 0]);

        $response = $this->get(route('shares.roster', $share->{EventShare::SHARE_TOKEN}));

        $response->assertOk();
        $response->assertViewIs('pages.shares.roster');

        $share->refresh();

        $this->assertSame(1, $share->{EventShare::VIEW_COUNT});
    }
}
