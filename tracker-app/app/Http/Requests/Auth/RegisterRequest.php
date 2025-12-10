<?php

namespace App\Http\Requests\Auth;

use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Auth\AtLeastOneOrganizationSelectedRule;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Handles validation for the user registration form.
 *
 * Provides the base validation rules (name, email, username, password, etc.) and
 * dynamically generates additional rules for any organizations returned by
 * `Organization::fullyLoaded()->get()` (identifier rules, region/unit rules).
 *
 * Notes:
 * - `prepareForValidation()` sanitizes phone numbers by stripping non-digits.
 * - `withValidator()` adds custom, user-facing messages for dynamically generated rules.
 *
 * @package App\Http\Requests\Auth
 * @see App\Models\Organization::fullyLoaded()
 * @property \Illuminate\Support\Collection|null $organizations Cached organizations used when generating rules
 */
class RegisterRequest extends FormRequest
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
     * @return array<string, mixed> The combined validation rules for the registration form.
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:256',
                Rule::unique(Trooper::class, Trooper::EMAIL)
            ],
            'phone' => ['nullable', 'string', 'max:10'],
            'account_type' => ['required', 'in:member,handler'],
            'username' => [
                'required',
                'string',
                Rule::unique(Trooper::class, Trooper::USERNAME),
            ],
            'password' => ['required', 'string'],
        ];

        $rules = array_merge($rules, $this->getOrganizationValidationRules());

        return $rules;
    }

    /**
     * Prepare the data for validation.
     *
     * This method sanitizes the phone number by removing any non-digit characters.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone'))
        {
            $this->merge([
                'phone' => preg_replace('/\D+/', '', $this->input('phone')),
            ]);
        }
    }

    /**
     * Generates dynamic validation rules for selected organizations.
     *
     * Fetches active organizations and constructs validation rules for their specific identifiers
     * (e.g., TKID, CAT #) and associated regions, applying custom rule objects.
     *
     * For handlers, organization identifiers are optional. For other account types,
     * identifiers are required when the organization is selected.
     *
     * @return array<string, mixed> An array of validation rules for the 'organizations' input.
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
            //  organization.*.identifier rules
            if (!empty($organization->identifier_validation))
            {
                // Parse the base validation rules (e.g., 'integer|between:1000,99999')
                $base_rules = explode('|', $organization->identifier_validation);

                // For members: require identifier when selected, validate format, and check uniqueness.
                // For handlers: all identifier rules are skipped (optional and unvalidated).
                $organization_rules = [
                    Rule::when(
                        fn() => $this->account_type === 'member',
                        array_merge(
                            [Rule::requiredIf(fn() => $this->input("organizations.{$organization->id}.selected") ?? false)],
                            $base_rules,
                            [new UniqueOrganizationIdentifierRule($organization)]
                        )
                    ),
                ];

                $rules["organizations.{$organization->id}.identifier"] = $organization_rules;
            }

            $regions = $organization->organizations;

            if ($regions->count() > 0)
            {
                // Require region when organization is selected
                $rules["organizations.{$organization->id}.region_id"] = [
                    Rule::requiredIf(fn() => $this->input("organizations.{$organization->id}.selected") ?? false),
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
                                ->whereIn('id', $units->pluck('id')),
                        ];
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * This method is used to add custom, user-friendly error messages for the
     * dynamically generated organization identifier rules.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $active_organizations = $this->getOrganizations();

        $messages = [];

        foreach ($active_organizations as $organization)
        {
            $key = "organizations.{$organization->id}.identifier";

            if (!empty($organization->identifier_validation))
            {
                $rules = explode('|', $organization->identifier_validation);

                foreach ($rules as $rule)
                {
                    $ruleName = $this->normalizeRuleKey($rule);

                    $messages["{$key}.{$ruleName}"] = "The {$organization->identifier_display} for {$organization->name} must be {$this->friendlyPhrase($rule)}.";
                }
            }

            $messages["{$key}"] = "The {$organization->identifier_display} for {$organization->name} is required";

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

    private function normalizeRuleKey(string $rule): string
    {
        // Laravel uses 'between' not 'between:1000,9999' for message keys
        return explode(':', $rule)[0];
    }

    private function friendlyPhrase(string $rule): string
    {
        return match ($rule)
        {
            'integer' => 'an integer',
            'string' => 'a valid string',
            default => str_replace(':', ' ', $rule),
        };
    }

    private function getOrganizations(): Collection
    {
        if (!isset($this->organizations))
        {
            $this->organizations = Organization::fullyLoaded()->get();
        }
        return $this->organizations;
    }
}