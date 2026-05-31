<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use App\Rules\Troopers\VisitorOrganizationRule;
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
 * - Selected organizations must exist and be descendants of the selected organization tree
 * - Identifiers follow organization-specific validation rules when provided
 *
 * Administrators and moderators can modify trooper membership settings.
 *
 * @property Collection|null $organizations_cache Cached organizations for validation
 */
class MembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * Verifies that the trooper exists in the route and that the authenticated
     * user is an administrator or moderator.
     *
     * @return bool Returns true if the user is an administrator or moderator
     *
     * @throws AuthorizationException If the trooper is not found in the route
     */
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return in_array($this->user()->membership_role, [
            MembershipRole::ADMINISTRATOR,
            MembershipRole::MODERATOR,
        ]);
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Generates dynamic validation rules for organization memberships.
     *
     * @return array<string, mixed> The validation rules for organization memberships
     */
    public function rules(): array
    {
        $rules = $this->getOrganizationValidationRules();

        return $rules;
    }

    /**
     * Get custom attribute names for validator errors
     *
     * This method generates user-friendly attribute names for organization
     * identifiers and assignments to improve error messages.
     *
     * @return array<string, string> Custom attribute names for validation errors
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->getOrganizations() as $organization)
        {
            $attributes["organizations.{$organization->id}.identifier"] = "{$organization->name} identifier";
            $attributes["organizations.{$organization->id}.assignment"] = "{$organization->name} assignment";
        }

        return $attributes;
    }

    /**
     * Generate dynamic validation rules for organization memberships
     *
     * Validates that selected organizations:
     * - Are required when an identifier is provided
     * - Exist in the database
     * - Have valid identifiers according to organization-specific rules
     *
     * @return array<string, mixed> Validation rules for organization memberships
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

            // Validate assignment - required when identifier is provided and must exist
            $rules["organizations.{$organization->id}.assignment"] = [
                Rule::requiredIf(fn () => !empty($this->input("organizations.{$organization->id}.identifier"))),
                'nullable',
                Rule::exists(Organization::class, Organization::ID),
                new VisitorOrganizationRule($trooper),
            ];
        }

        return $rules;
    }

    /**
     * Retrieve and cache all organizations for validation
     *
     * Fetches all active organizations and caches them to avoid multiple
     * database queries during validation rule generation.
     *
     * @return Collection The collection of active organizations
     */
    private function getOrganizations(): Collection
    {
        $getter = function (): Collection {
            return Organization::ofTypeOrganizations()
                ->orderBy(Organization::NAME)
                ->get();
        };

        $organizations = once($getter);

        return $organizations;
    }
}
