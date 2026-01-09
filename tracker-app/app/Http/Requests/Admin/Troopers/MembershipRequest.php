<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Admin\Troopers\OrganizationLeafNodeRule;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles the validation for updating a trooper's organization memberships.
 *
 * This class defines validation rules for managing a trooper's organization memberships
 * using a popup picker to select specific organizations. The validation ensures:
 * - Organization IDs are required when an identifier is provided
 * - Selected organizations must exist and be leaf nodes (no children)
 * - Identifiers follow organization-specific validation rules when provided
 *
 * Only administrators can modify trooper membership settings.
 *
 * @property \Illuminate\Database\Eloquent\Collection|null $organizations_cache Cached organizations for validation
 */
class MembershipRequest extends FormRequest
{
    private ?Collection $organizations_cache = null;

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
     * Generates dynamic validation rules for organization memberships.
     * Organizations are selected via popup picker and must be leaf nodes.
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
     * Validates that selected organizations:
     * - Are required when an identifier is provided
     * - Exist in the database
     * - Are leaf nodes (have no child organizations)
     * - Have valid identifiers according to organization-specific rules
     *
     * @return array<string, mixed> Validation rules for organization memberships.
     */
    private function getOrganizationValidationRules(): array
    {
        $trooper = $this->route('trooper');

        $rules = [
            'organizations' => ['array'],
        ];

        // Add identifier and assignment validation for each organization
        $organizations = $this->getOrganizations();

        foreach ($organizations as $organization)
        {
            // Validate identifier if organization requires it
            if (!empty($organization->identifier_validation))
            {
                $base_rules = explode('|', $organization->identifier_validation);

                $rules["organizations.{$organization->id}.identifier"] = array_merge(
                    ['nullable'],
                    $base_rules,
                    [new UniqueOrganizationIdentifierRule($organization, $trooper)]
                );
            }

            // Validate assignment - required when identifier is provided, must be a leaf node and descendant
            $rules["organizations.{$organization->id}.assignment"] = [
                Rule::requiredIf(fn() => !empty($this->input("organizations.{$organization->id}.identifier"))),
                'nullable',
                Rule::exists(Organization::class, Organization::ID),
                new OrganizationLeafNodeRule($organization),
            ];
        }

        return $rules;
    }

    /**
     * Retrieve and cache all organizations for validation.
     *
     * Fetches all active organizations and caches them to avoid multiple
     * database queries during validation rule generation.
     *
     * @return \Illuminate\Database\Eloquent\Collection The collection of active organizations.
     */
    private function getOrganizations(): Collection
    {
        if (!isset($this->organizations_cache))
        {
            $this->organizations_cache = Organization::ofTypeOrganizations()->get();
        }
        return $this->organizations_cache;
    }
}