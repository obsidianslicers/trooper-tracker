<?php

declare(strict_types=1);

namespace App\Features\Organizations\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Organization;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving organizations for picker/dropdown components.
 *
 * Processes GetOrganizationsForPickerQuery to return organizations based on:
 * - Moderation permissions: Returns only orgs the trooper moderates
 * - Organization hierarchy: Returns a specific org and its descendants (via node_path)
 * - All organizations: Returns all organizations when no filters applied
 *
 * All results are ordered by organization sequence for consistent UI display.
 *
 * @implements QueryHandlerInterface<GetOrganizationsForPickerQuery>
 */
readonly class GetOrganizationsForPickerQueryHandler implements QueryHandlerInterface
{
    /**
     * Handle the query to retrieve organizations for picker components.
     *
     * Query behavior:
     * 1. If moderated_only is true: Returns organizations moderated by the trooper
     * 2. If organization_id is set: Returns the specified organization and all descendants
     *    (matched using the organization's node_path for hierarchical filtering)
     * 3. Otherwise: Returns all organizations
     *
     * @param  GetOrganizationsForPickerQuery  $message  The query containing filter criteria
     * @return Collection<int, Organization> Collection of organizations
     */
    public function __invoke(object $message): mixed
    {
        $organizations = collect([]);

        if ($message->moderated_only)
        {
            $organizations = Organization::moderatedBy($message->trooper)
                ->orderBy(Organization::SEQUENCE)
                ->get();
        }
        elseif ($message->organization_id !== null)
        {
            // Pre-select a specific organization if organization_id is provided
            $organization_id = $message->organization_id;

            $organization = Organization::findOrFail($organization_id);

            $organizations = Organization::where(Organization::NODE_PATH, 'like', $organization->node_path.'%')
                ->orderBy(Organization::SEQUENCE)
                ->get();
        }
        else
        {
            $organizations = Organization::orderBy(Organization::SEQUENCE)->get();
        }

        return $organizations;
    }
}
