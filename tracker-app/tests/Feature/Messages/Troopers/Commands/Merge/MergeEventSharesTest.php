<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeEventShares;
use App\Models\EventShare;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeEventSharesTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_event_shares_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_share = EventShare::factory()
            ->forTrooper($source_trooper)
            ->create();

        $trashed_share = EventShare::factory()
            ->forTrooper($source_trooper)
            ->create();
        $trashed_share->delete();

        MergeEventShares::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_event_shares', [
            EventShare::ID => $active_share->id,
            EventShare::TROOPER_ID => $target_trooper->id,
            EventShare::DELETED_AT => null,
        ]);

        $this->assertSoftDeleted('tt_event_shares', [
            EventShare::ID => $trashed_share->id,
            EventShare::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_shares', [
            EventShare::ID => $active_share->id,
            EventShare::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_event_shares', [
            EventShare::ID => $trashed_share->id,
            EventShare::TROOPER_ID => $source_trooper->id,
        ]);
    }
}
