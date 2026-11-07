<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Queries;

use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Query to retrieve organizations for use in picker/dropdown components.
 *
 * This query supports four modes:
 * 1. Event host only: Returns only organizations the trooper may host events for
 * 2. Moderated only: Returns only organizations the trooper moderates
 * 3. Specific organization: Returns an organization and all its descendants (via node_path)
 * 4. All organizations: Returns all organizations in sequence order
 *
 * The results are always ordered by the organization's sequence field.
 *
 * @method static Collection<int, Organization> call(Trooper $trooper, array $data)
 */
final class GetOrganizationsForPicker extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper requesting organizations
     * @param  array  $data  Query parameters:
     *                       - 'moderated_only' (bool): Filter to moderated organizations only (default: false)
     *                       - 'event_host_only' (bool): Filter to organizations the trooper may host events for (default: false)
     *                       - 'organization_id' (int|null): Filter to specific org and descendants (default: null)
     *                       - 'depth_max' (int|null): Maximum org depth to include (default: null = all depths)
     */
    public function __construct(private readonly Trooper $trooper, array $data)
    {
        $this->moderated_only = boolval($data['moderated_only'] ?? false);
        $this->event_host_only = boolval($data['event_host_only'] ?? false);
        $this->organization_id = empty($data['organization_id']) ? null : intval($data['organization_id']);
        $this->depth_max = isset($data['depth_max']) && $data['depth_max'] !== null ? intval($data['depth_max']) : null;
    }

    /**
     * Handle the query to retrieve organizations for picker components.
     *
     * Query behavior:
     * 1. If event_host_only (or moderated_only) is true: Returns organizations the trooper
     *    may host events for (or moderates)
     * 2. If organization_id is set: Returns the specified organization and all descendants
     *    (matched using the organization's node_path for hierarchical filtering)
     * 3. Otherwise: Returns all organizations
     *
     * @return Collection<int, Organization> Collection of organizations
     */
    public function handle(): Collection
    {
        $organizations = collect([]);

        if ($this->event_host_only)
        {
            $organizations = Organization::eventHostableBy($this->trooper)
                ->orderBy(Organization::SEQUENCE)
                ->get();
        }
        elseif ($this->moderated_only)
        {
            $organizations = Organization::moderatedBy($this->trooper)
                ->orderBy(Organization::SEQUENCE)
                ->get();
        }
        elseif ($this->organization_id !== null)
        {
            // Pre-select a specific organization if organization_id is provided
            $organization_id = $this->organization_id;

            $organization = Organization::findOrFail($organization_id);

            $organizations = Organization::withinNodePath($organization->node_path)
                ->orderBy(Organization::SEQUENCE)
                ->get();
        }
        else
        {
            $organizations = Organization::orderBy(Organization::SEQUENCE)->get();
        }

        if ($this->depth_max !== null)
        {
            $organizations = $organizations->filter(fn (Organization $org) => $org->depth <= $this->depth_max)->values();
        }

        return $organizations;
    }

    /**
     * Whether to filter to only organizations moderated by the trooper.
     */
    public readonly bool $moderated_only;

    /**
     * Whether to filter to organizations the trooper may host events for.
     */
    public readonly bool $event_host_only;

    /**
     * Optional organization ID to filter by (includes descendants via node_path).
     */
    public readonly ?int $organization_id;

    /**
     * Optional maximum depth to include. When set, only orgs at depth <= depth_max are returned.
     */
    public readonly ?int $depth_max;
}
