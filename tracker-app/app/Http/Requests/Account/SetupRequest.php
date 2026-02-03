<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Admin\Troopers\OrganizationLeafNodeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Validates profile setup organization selection and hierarchical assignments.
 *
 * Generates dynamic validation rules for each organization, ensuring regions and units
 * are properly selected when an organization is chosen. Fetches active organizations
 * via `Organization::fullyLoaded()` and constructs rules for region and unit fields.
 *
 * Key behaviors:
 * - `prepareForValidation()` sanitizes phone numbers by removing non-digit characters.
 * - `withValidator()` attaches custom, user-facing error messages for organization rules.
 * - `getOrganizations()` caches organizations for efficient repeated access.
 *
 * @property \Illuminate\Support\Collection|null $organizations Cached organizations for rule generation
 */
class SetupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool Returns true as registration is open to guests
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * Combines base email rules with dynamically generated organization hierarchy rules.
     *
     * @return array<string, mixed> The combined validation rules for the setup form
     */
    public function rules(): array
    {
        $rules = [
            Trooper::EMAIL => [
                'required',
                'string',
                'email:rfc',
                'max:256',
                Rule::unique(Trooper::class, Trooper::EMAIL)
                    ->ignore($this->user()->id, Trooper::ID),
            ],
            Trooper::NOTIFICATION_FREQUENCY => [
                'required',
                'string',
                'max:16',
                'in:' . NotificationFrequency::toValidator(),
            ],
        ];

        $rules = array_merge($rules, $this->getOrganizationValidationRules());

        return $rules;
    }

    /**
     * Generate dynamic validation rules for organization memberships
     *
     * Validates that selected organizations:
     * - Exist in the database
     * - Are leaf nodes (have no child organizations)
     * - Have valid identifiers according to organization-specific rules
     *
     * @return array<string, mixed> Validation rules for organization memberships
     */
    private function getOrganizationValidationRules(): array
    {
        $rules = [
            'organizations' => ['array'],
        ];

        // Add identifier and assignment validation for each organization
        $organizations = $this->getOrganizations();

        foreach ($organizations as $organization)
        {
            // Validate assignment - required when identifier is provided, must be a leaf node and descendant
            $rules["organizations.{$organization->id}.assignment"] = [
                'nullable',
                Rule::exists(Organization::class, Organization::ID),
                new OrganizationLeafNodeRule($organization),
            ];
        }

        return $rules;
    }

    /**
     * Get or cache the fully loaded organization hierarchy for validation
     *
     * Retrieves organizations with regions and units preloaded, caching the result
     * to avoid multiple database queries during validation rule generation.
     *
     * @return Collection The collection of active organizations with hierarchy
     */
    private function getOrganizations(): Collection
    {
        $getter = function (): Collection
        {
            return Organization::fullyLoaded()->get();
        };

        $organizations = once($getter);

        return $organizations;
    }
}
