<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\HasNormalizers;
use App\Models\Organization;
use App\Rules\Auth\AtLeastOneOrganizationSelectedRule;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ResubmitDeniedRequest extends FormRequest
{
    use HasNormalizers;

    public function authorize(): bool
    {
        return $this->user()?->is_denied === true;
    }

    public function rules(): array
    {
        return $this->getOrganizationValidationRules();
    }

    protected function prepareForValidation(): void
    {
        $organizations = $this->normalizeBooleanFields(
            $this->input('organizations', []),
            ['selected']
        );

        $this->merge(['organizations' => $organizations]);
    }

    public function withValidator(Validator $validator): void
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

    private function getOrganizationValidationRules(): array
    {
        $rules = [
            'organizations'           => ['array', new AtLeastOneOrganizationSelectedRule],
            'organizations.*.selected' => ['nullable', 'boolean'],
        ];

        $account_type = $this->resolveAccountType();
        $trooper      = $this->user();
        $organizations = $this->getOrganizations();

        foreach ($organizations as $organization)
        {
            if (!empty($organization->identifier_validation))
            {
                $base_rules = explode('|', $organization->identifier_validation);

                $org_rules = [
                    'nullable',
                    Rule::when(
                        fn () => $account_type === 'member' && $this->input("organizations.{$organization->id}.selected") === '1',
                        array_merge(
                            ['required'],
                            $base_rules,
                            [new UniqueOrganizationIdentifierRule($organization, $trooper)]
                        )
                    ),
                    Rule::when(
                        fn () => $account_type === 'visitor' && !empty($this->input("organizations.{$organization->id}.identifier")),
                        array_merge(
                            $base_rules,
                            [new UniqueOrganizationIdentifierRule($organization, $trooper)]
                        )
                    ),
                ];

                $rules["organizations.{$organization->id}.identifier"] = $org_rules;
            }

            $regions = $organization->organizations;

            if ($regions->count() > 0)
            {
                $rules["organizations.{$organization->id}.region_id"] = [
                    Rule::requiredIf(fn () => $account_type !== 'visitor' && $this->input("organizations.{$organization->id}.selected") === '1'),
                    Rule::when(
                        fn () => $account_type !== 'visitor' && $this->input("organizations.{$organization->id}.selected") === '1',
                        Rule::exists(Organization::class, Organization::ID)
                            ->whereIn('id', $regions->pluck('id'))
                    ),
                ];

                foreach ($regions as $region)
                {
                    $units = $region->organizations;

                    if ($units->count() > 0)
                    {
                        $rules["organizations.{$organization->id}.unit_id"] = [
                            Rule::requiredIf(fn () => $account_type !== 'visitor' && $this->input("organizations.{$organization->id}.region_id") == $region->id),
                            Rule::when(
                                fn () => $account_type !== 'visitor' && $this->input("organizations.{$organization->id}.selected") === '1' && !empty($this->input("organizations.{$organization->id}.unit_id")),
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

    private function resolveAccountType(): string
    {
        $role = $this->user()?->membership_role?->value ?? 'member';

        return in_array($role, ['visitor', 'handler'], true) ? $role : 'member';
    }

    private function getOrganizations(): Collection
    {
        return once(fn (): Collection => Organization::fullyLoaded()->get());
    }

    private function normalizeRuleKey(string $rule): string
    {
        return explode(':', $rule)[0];
    }

    private function friendlyPhrase(string $rule): string
    {
        return match ($rule)
        {
            'integer' => 'an integer',
            'string'  => 'a valid string',
            default   => str_replace(':', ' ', $rule),
        };
    }
}
