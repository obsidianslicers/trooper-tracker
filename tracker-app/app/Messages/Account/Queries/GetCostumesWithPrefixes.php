<?php

declare(strict_types=1);

namespace App\Messages\Account\Queries;

use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Retrieves the details of an account.
 *
 * This query message responds with the account details, which can be used by frontend clients
 * to display and manage account information.
 *
 * @method static Collection call(Trooper $trooper)
 */
final class GetCostumesWithPrefixes extends Message
{
    public function __construct(
        public readonly Trooper $trooper
    ) {}

    /**
     * Retrieves the details of an account.
     *
     * @return Trooper The account details
     */
    public function handle(): Collection
    {
        $with = [
            'organization_costume.costume',
            'organization_costume.organization',
        ];

        return TrooperCostume::query()
            ->with($with)
            ->where(TrooperCostume::TROOPER_ID, $this->trooper->id)
            ->whereHas('organization_costume', function ($query) {
                $query->whereNotNull(OrganizationCostume::PREFIX);
            })
            ->get();
    }
}
