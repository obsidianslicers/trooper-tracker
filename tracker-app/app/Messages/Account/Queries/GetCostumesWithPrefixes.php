<?php

declare(strict_types=1);

namespace App\Messages\Account\Queries;

use App\Models\Trooper;
use Hyperdrive\Message;
use App\Models\TrooperCostume;
use App\Models\OrganizationCostume;
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
    ) {
    }

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
            ->whereHas('organization_costume', function ($query)
            {
                $query->whereNotNull(OrganizationCostume::PREFIX);
            })
            ->get();

        // ->mapWithKeys(function (TrooperCostume $tc)
        // {
        //     $organization_costume = $tc->organization_costume;
        //     $trooper_organization = $this->trooper->organizations->firstWhere('id', $organization_costume->organization_id);

        //     $identifier = ($organization_costume->prefix ?? '') . ($trooper_organization?->pivot?->identifier ?? '');
        //     $costume_name = $organization_costume->costume?->name ?? '';
        //     $organization_name = $organization_costume->organization?->name ?? '';

        //     $label = "$identifier — $costume_name ($organization_name)";

        //     return [$tc->id => $label];
        // })
        // ->toArray();
    }
}
