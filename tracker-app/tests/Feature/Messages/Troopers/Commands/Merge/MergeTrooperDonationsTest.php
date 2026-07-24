<?php

declare(strict_types=1);

namespace Tests\Feature\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeTrooperDonations;
use App\Models\Trooper;
use App\Models\TrooperDonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeTrooperDonationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_donations_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_donation = TrooperDonation::factory()
            ->forTrooper($source_trooper)
            ->create();

        $trashed_donation = TrooperDonation::factory()
            ->forTrooper($source_trooper)
            ->create();
        $trashed_donation->delete();

        MergeTrooperDonations::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_trooper_donations', [
            TrooperDonation::ID => $active_donation->id,
            TrooperDonation::TROOPER_ID => $target_trooper->id,
            TrooperDonation::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas('tt_trooper_donations', [
            TrooperDonation::ID => $trashed_donation->id,
            TrooperDonation::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertSoftDeleted('tt_trooper_donations', [
            TrooperDonation::ID => $trashed_donation->id,
            TrooperDonation::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_donations', [
            TrooperDonation::ID => $active_donation->id,
            TrooperDonation::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_donations', [
            TrooperDonation::ID => $trashed_donation->id,
            TrooperDonation::TROOPER_ID => $source_trooper->id,
        ]);
    }
}