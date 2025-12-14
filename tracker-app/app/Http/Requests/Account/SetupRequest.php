<?php

namespace App\Http\Requests\Account;

use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Auth\AtLeastOneOrganizationSelectedRule;
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
 * @package App\Http\Requests\Account
 * @property \Illuminate\Support\Collection|null $organizations Cached organizations for rule generation
 */
class SetupRequest extends FormRequest
{
    private ?Collection $organizations = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true as registration is open to guests.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Combines base email rules with dynamically generated organization hierarchy rules.
     *
     * @return array<string, mixed> The combined validation rules for the setup form.
     */
    public function rules(): array
    {
        $rules = [
            'email' => [
                'required',
                'string',
                'email',
                'max:256',
                Rule::unique(Trooper::class, Trooper::EMAIL)
                    ->ignore($this->user()->id, Trooper::ID),
            ],
        ];

        $rules = array_merge($rules, $this->getOrganizationValidationRules());

        return $rules;
    }

    /**
     * Generate dynamic rules for organization selection, regions, and units.
     *
     * Fetches active organizations and constructs conditional rules for each:
     * - At least one organization must be selected.
     * - When an organization is selected, its region becomes required.
     * - When a region with units is selected, the unit becomes required.
     *
     * Custom rule objects validate that selected regions and units belong to the correct parent.
     *
     * @return array<string, mixed> Validation rules for organizations and their hierarchy.
     */
    private function getOrganizationValidationRules(): array
    {
        $rules = [
            'organizations' => ['array', new AtLeastOneOrganizationSelectedRule()],
            'organizations.*.selected' => ['nullable', 'boolean'],
        ];

        $organizations = $this->getOrganizations();

        foreach ($organizations as $organization)
        {
            $regions = $organization->organizations;

            if ($regions->count() > 0)
            {
                // Require region when organization is selected
                $rules["organizations.{$organization->id}.region_id"] = [
                    Rule::requiredIf(fn() => $this->input("organizations.{$organization->id}.selected") == 1 ?? false),
                    Rule::exists(Organization::class, Organization::ID)
                        ->whereIn('id', $regions->pluck('id'))
                ];

                // For each region, check if it has units and require unit_id accordingly
                foreach ($regions as $region)
                {
                    $units = $region->organizations;

                    if ($units->count() > 0)
                    {
                        // Require unit when this specific region is selected
                        $rules["organizations.{$organization->id}.unit_id"] = [
                            Rule::requiredIf(fn() => $this->input("organizations.{$organization->id}.region_id") == $region->id),
                            Rule::exists(Organization::class, Organization::ID)
                                ->whereIn('id', $units->pluck('id'))
                        ];
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Attach custom error messages for organization hierarchy validation rules.
     *
     * Maps dynamically generated rule keys to user-friendly messages indicating which
     * organization, region, or unit requires selection.
     *
     * @param \Illuminate\Validation\Validator $validator The validator instance.
     * @return void
     */
    public function withValidator($validator): void
    {
        $active_organizations = $this->getOrganizations();

        $messages = [];

        foreach ($active_organizations as $organization)
        {
            foreach ($organization->organizations as $region)
            {
                $region_key = "organizations.{$organization->id}.region_id";

                $messages["{$region_key}"] = "Please select a region for {$organization->name}.";

                foreach ($region->organizations as $unit)
                {
                    $unit_key = "organizations.{$organization->id}.unit_id";

                    $messages["{$unit_key}"] = "Please select a unit for {$organization->name}-{$region->name}.";
                }
            }
        }

        $validator->setCustomMessages($messages);
    }

    /**
     * Get or cache the fully loaded organization hierarchy for validation.
     *
     * Retrieves organizations with regions and units preloaded, caching the result
     * to avoid multiple database queries during validation rule generation.
     *
     * @return Collection The collection of active organizations with hierarchy.
     */
    private function getOrganizations(): Collection
    {
        if (!isset($this->organizations))
        {
            $this->organizations = Organization::fullyLoaded()->get();
        }
        return $this->organizations;
    }
}