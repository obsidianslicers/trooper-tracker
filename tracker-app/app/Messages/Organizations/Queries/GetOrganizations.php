<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Queries;

use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Retrieves the list of all organizations.
 *
 * This query message responds with the organizational hierarchy data, which can be used by frontend clients
 * to display the structure of the organization and manage access control based on roles and teams.
 *
 * @method static Collection call()
 */
final class GetOrganizations extends Message
{
    /**
     * Retrieves all organizations as a collection.
     *
     * @return Collection A collection of all organizations with their respective IDs and names
     */
    public function handle(): Collection
    {
        $getter = fn () => Organization::query()->ofTypeOrganizations()->get();

        $organizations = once($getter);

        return $organizations;
    }
}
