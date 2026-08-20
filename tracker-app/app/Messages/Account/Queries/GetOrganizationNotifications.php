<?php

declare(strict_types=1);

namespace App\Messages\Account\Queries;

use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
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
final class GetOrganizationNotifications extends Message
{
    public function __construct(
        public readonly Trooper $trooper
    ) {}

    /**
     * Retrieves the details of an account.
     *
     * @return Collection Organizations with enabled flags
     */
    public function handle(): Collection
    {
        $organizations = GetOrganizationHierarchy::call();

        $trooper_assignments = $this->trooper->trooper_assignments()
            ->where(TrooperAssignment::SHOULD_NOTIFY, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->toArray();

        foreach ($organizations as $organization)
        {
            $organization->enabled = in_array($organization->id, $trooper_assignments);

            foreach ($organization->organizations as $region)
            {
                $region->enabled = in_array($region->id, $trooper_assignments);

                foreach ($region->organizations as $unit)
                {
                    $unit->enabled = in_array($unit->id, $trooper_assignments);
                }
            }
        }

        return $organizations;
    }
}
