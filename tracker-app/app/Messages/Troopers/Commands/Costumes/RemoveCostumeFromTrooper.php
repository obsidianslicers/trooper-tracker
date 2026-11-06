<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;

/**
 * Command message for deleting a costume from a trooper's profile.
 *
 * @method static void call(Trooper $trooper, int $costume_id)
 */
final class RemoveCostumeFromTrooper extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly int $costume_id,
    ) {}

    /**
     * Execute the command to delete a costume from the trooper's profile.
     *
     * @return null
     */
    public function handle(): void
    {
        $trooper_costumes = TrooperCostume::withPending()
            ->where(TrooperCostume::TROOPER_ID, $this->trooper->id)
            ->whereHas('organization_costume', function ($query)
            {
                $query->where(OrganizationCostume::COSTUME_ID, $this->costume_id);
            })
            ->get();

        foreach ($trooper_costumes as $trooper_costume)
        {
            $trooper_costume->delete();
        }
    }
}
