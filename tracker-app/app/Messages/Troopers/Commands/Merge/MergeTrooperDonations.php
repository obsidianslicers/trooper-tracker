<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\Trooper;
use App\Models\TrooperDonation;
use Hyperdrive\Message;

/**
 * Merges the costumes of two troopers.
 * This command ensures that all costumes of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeTrooperDonations extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {}

    public function handle(): void
    {
        $source_donations = TrooperDonation::query()
            ->withTrashed()
            ->where(TrooperDonation::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(TrooperDonation::ID)
            ->get();

        foreach ($source_donations as $source_donation)
        {
            $source_donation->trooper_id = $this->target_trooper->id;
            $source_donation->save();
        }
    }
}
