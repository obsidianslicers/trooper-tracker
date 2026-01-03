<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating a trooper's organization memberships.
 *
 * This class defines validation rules for managing a trooper's organization memberships,
 * including their selected organizations, regions, and units. The validation ensures:
 * - Region IDs are optional but must exist and belong to the parent organization
 * - Unit IDs are required when their parent region is selected and must exist within that region
 *
 * Only administrators can modify trooper membership settings.
 *
 * @property \Illuminate\Database\Eloquent\Collection|null $organizationsCache Cached organizations for rule generation
 */
class MembershipRequest extends FormRequest
{
    private ?Collection $organizationsCache = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Verifies that the trooper exists in the route and that the authenticated
     * user is an administrator. Only administrators can modify membership settings.
     *
     * @return bool Returns true if the user is an administrator.
     * @throws AuthorizationException If the trooper is not found in the route.
     */
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->membership_role == MembershipRole::ADMINISTRATOR;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Generates dynamic validation rules for organization memberships based on the
     * organizational hierarchy (organizations → regions → units).
     *
     * @return array<string, mixed> The validation rules for organization memberships.
     */
    public function rules(): array
    {
        $rules = $this->getOrganizationValidationRules();

        return $rules;
    }

    /**
     * Generate dynamic validation rules for organization memberships.
     *
     * Fetches active organizations and constructs conditional rules for each:
     * - Region IDs are optional but must exist and belong to the selected organization
     * - Unit IDs are required when a region with units is selected
     * - Unit IDs must exist and belong to the selected region
     *
     * @return array<string, mixed> Validation rules for the organizational hierarchy.
     */
    private function getOrganizationValidationRules(): array
    {
        $rules = [];

        $organizations = $this->getOrganizations();

        foreach ($organizations as $organization)
        {
            $regions = $organization->organizations;
            $region_ids = $regions->pluck('id')->toArray();

            // Always validate region_id if provided, even if organization has no regions
            $rules["organizations.{$organization->id}.region_id"] = [
                'nullable',
                Rule::exists(Organization::class, Organization::ID)
                    ->whereIn('id', $region_ids)
            ];

            // Collect all units from all regions for this organization
            $all_unit_ids = [];
            $regions_requiring_units = [];

            foreach ($regions as $region)
            {
                $units = $region->organizations;
                if ($units->count() > 0)
                {
                    $all_unit_ids = array_merge($all_unit_ids, $units->pluck('id')->toArray());
                    $regions_requiring_units[] = $region->id;
                }
            }

            // Always validate unit_id to ensure it belongs to a valid unit within this organization
            $rules["organizations.{$organization->id}.unit_id"] = [
                Rule::requiredIf(fn() => in_array($this->input("organizations.{$organization->id}.region_id"), $regions_requiring_units)),
                'nullable',
                Rule::exists(Organization::class, Organization::ID)
                    ->whereIn('id', $all_unit_ids)
            ];
        }

        return $rules;
    }

    /**
     * Retrieve and cache active organizations with their full hierarchy.
     *
     * This method fetches all active organizations with their nested relationships
     * (regions and units) and caches them in an instance variable to avoid multiple
     * database queries during validation rule generation.
     *
     * @return \Illuminate\Database\Eloquent\Collection The collection of active organizations with hierarchy.
     */
    private function getOrganizations(): Collection
    {
        if (!isset($this->organizationsCache))
        {
            $this->organizationsCache = Organization::fullyLoaded()->get();
        }
        return $this->organizationsCache;
    }
}