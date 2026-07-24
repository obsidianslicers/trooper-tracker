<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Auth\AtLeastOneOrganizationSelectedRule;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Handles validation for the user registration form.
 *
 * Provides the base validation rules (name, email, password, etc.) and
 * dynamically generates additional rules for any organizations returned by
 * `Organization::fullyLoaded()->get()` (identifier rules, region/unit rules).
 *
 * Notes:
 * - `prepareForValidation()` sanitizes phone numbers by stripping non-digits.
 * - `withValidator()` adds custom, user-facing messages for dynamically generated rules.
 *
 * @see App\Models\Organization::fullyLoaded()
 *
 * @property Collection|null $organizations Cached organizations used when generating rules
 */
class RegisterRequest extends FormRequest
{
    use HasNormalizers;

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
     * @return array<string, mixed> The combined validation rules for the registration form
     */
    public function rules(): array
    {
        $rules = [
            Trooper::LEGAL_NAME => ['required', 'string', 'max:255'],
            Trooper::DISPLAY_NAME => ['required', 'string', 'max:255'],
            Trooper::EMAIL => [
                'required',
                'string',
                'email',
                'max:256',
                Rule::unique(Trooper::class, Trooper::EMAIL),
            ],
            Trooper::PHONE => [
                'nullable',
                'string',
                'max:16',
            ],
            Trooper::MEMBERSHIP_ROLE => [
                'required',
                'in:member,handler,visitor',
            ],
            Trooper::DATE_OF_BIRTH => [
                Rule::requiredIf(fn (): bool => $this->requiresGuardianForSelectedOrganizations()),
                'nullable',
                'date',
                // Must be younger than 18 (Born AFTER 18 years ago)
                'after:'.now()->subYears(18)->toDateString(),
                // Must be older than 13 (Born BEFORE 13 years ago)
                'before:'.now()->subYears(13)->toDateString(),
            ],
            'guardian_email' => [
                Rule::requiredIf(fn (): bool => $this->requiresGuardianForSelectedOrganizations()),
                'nullable',
                'string',
                'email',
                'max:256',
                Rule::exists(Trooper::class, Trooper::EMAIL)->whereNull(Trooper::GUARDIAN_ID),
            ],
        ];

        $registration_auth = Session::get('registration_auth');

        if ($registration_auth && $registration_auth['method'] === 'email')
        {
            $rules['password'] = ['required', 'string'];
        }

        $rules = array_merge($rules, $this->getOrganizationValidationRules());

        return $rules;
    }

    /**
     * Prepare the data for validation
     *
     * This method sanitizes the phone number by removing any non-digit characters.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has(Trooper::PHONE) && !empty($this->input(Trooper::PHONE)))
        {
            $phone = $this->normalizePhoneInput($this->input(Trooper::PHONE));

            $this->merge([Trooper::PHONE => $phone]);
        }

        $registration_auth = Session::get('registration_auth');

        if ($registration_auth && $registration_auth[Trooper::EMAIL] != null)
        {
            $this->merge([
                Trooper::EMAIL => trim($registration_auth[Trooper::EMAIL]),
            ]);
        }

        $organizations = $this->normalizeBooleanFields(
            $this->input('organizations', []),
            ['selected']
        );

        $this->merge(['organizations' => $organizations]);
    }

    /**
     * Generates dynamic validation rules for selected organizations
     *
     * Fetches active organizations and constructs validation rules for their specific identifiers
     * (e.g., TKID, CAT #) and associated regions, applying custom rule objects.
     *
     * For handlers, organization identifiers are optional. For other account types,
     * identifiers are required when the organization is selected.
     *
     * @return array<string, mixed> An array of validation rules for the 'organizations' input
     */
    private function getOrganizationValidationRules(): array
    {
        $rules = [
            'organizations' => [
                'array',
                Rule::when(
                    fn () => $this->membership_role !== 'handler',
                    [new AtLeastOneOrganizationSelectedRule]
                ),
            ],
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

                // For members: required + format + uniqueness when selected.
                // For visitors: format + uniqueness only when a value is provided (optional).
                // For handlers: all identifier rules are skipped.
                $organization_rules = [
                    'nullable',
                    Rule::when(
                        fn () => $this->membership_role === 'member' && $this->input("organizations.{$organization->id}.selected") === true,
                        array_merge(
                            ['required'],
                            $base_rules,
                            [new UniqueOrganizationIdentifierRule($organization)]
                        )
                    ),
                    Rule::when(
                        fn () => $this->membership_role === 'visitor' && !empty($this->input("organizations.{$organization->id}.identifier")),
                        array_merge(
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
                // Require region when organization is selected (visitors skip region/unit)
                $rules["organizations.{$organization->id}.region_id"] = [
                    Rule::requiredIf(fn () => $this->membership_role !== 'visitor' && $this->input("organizations.{$organization->id}.selected") === true),
                    Rule::when(
                        fn () => $this->membership_role !== 'visitor' && $this->input("organizations.{$organization->id}.selected") === true,
                        Rule::exists(Organization::class, Organization::ID)
                            ->whereIn('id', $regions->pluck('id'))
                    ),
                ];

                // For each region, check if it has units and require unit_id accordingly
                foreach ($regions as $region)
                {
                    $units = $region->organizations;

                    if ($units->count() > 0)
                    {
                        // Require unit when this specific region is selected (visitors skip)
                        $rules["organizations.{$organization->id}.unit_id"] = [
                            Rule::requiredIf(fn () => $this->membership_role !== 'visitor' && $this->input("organizations.{$organization->id}.region_id") == $region->id),
                            Rule::when(
                                fn () => $this->membership_role !== 'visitor' && $this->input("organizations.{$organization->id}.selected") === true && !empty($this->input("organizations.{$organization->id}.unit_id")),
                                Rule::exists(Organization::class, Organization::ID)
                                    ->whereIn('id', $units->pluck('id'))
                            ),
                        ];
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Configure the validator instance
     *
     * This method is used to add custom, user-friendly error messages for the
     * dynamically generated organization identifier rules.
     *
     * @param  Validator  $validator
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
                // $messages["{$key}"] = "The {$organization->identifier_display} for {$organization->name} is required";

                $rules = explode('|', string: $organization->identifier_validation);

                foreach ($rules as $rule)
                {
                    $rule_name = $this->normalizeRuleKey($rule);

                    $messages["{$key}.{$rule_name}"] = "The {$organization->identifier_display} for {$organization->name} must be {$this->friendlyPhrase($rule)}.";
                }
            }

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

    private function getOrganizations(): Collection
    {
        $getter = function (): Collection {
            return Organization::fullyLoaded()->get();
        };

        $organizations = once($getter);

        return $organizations;
    }

    private function requiresGuardianForSelectedOrganizations(): bool
    {
        $selected_organization_ids = collect($this->input('organizations', []))
            ->filter(fn (mixed $organization_data): bool => is_array($organization_data) && filter_var(
                $organization_data['selected'] ?? false,
                FILTER_VALIDATE_BOOLEAN
            )
            )
            ->keys()
            ->map(fn (string|int $organization_id): int => (int) $organization_id);

        if ($selected_organization_ids->isEmpty())
        {
            return false;
        }

        return $this->getOrganizations()
            ->whereIn(Organization::ID, $selected_organization_ids)
            ->contains(
                fn (Organization $organization): bool => $organization->requires_guardian
            );
    }
}
