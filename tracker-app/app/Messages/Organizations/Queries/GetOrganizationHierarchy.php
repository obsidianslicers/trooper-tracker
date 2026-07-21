<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Queries;

use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Retrieves the hierarchical structure of an organization, including departments, teams, and roles.
 *
 * This query message responds with the organizational hierarchy data, which can be used by frontend clients
 * to display the structure of the organization and manage access control based on roles and teams.
 * 
 * @method static Collection call(...$args)
 * 
 */
final class GetOrganizationHierarchy extends Message
{
    /**
     * Retrieves the hierarchical structure of an organization as a nested associative array.
     *
     * @return Collection An array representing the organizational hierarchy, including organizations, regions, and units with their respective IDs and names    
     */
    public function handle(): Collection
    {
        //  TODO rename fullyLoaded to hierarchical
        return Organization::query()->fullyLoaded()->get();
    }
}