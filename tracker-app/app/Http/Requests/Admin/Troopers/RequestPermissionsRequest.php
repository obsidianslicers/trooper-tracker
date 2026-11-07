<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Messages\Troopers\Queries\Permissions\GetModerationChanges;
use App\Messages\Troopers\Queries\Permissions\GetRequestableOrganizations;
use App\Models\Organization;
use App\Models\Trooper;
use App\Plugins\PluginPermissionRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a moderator's request to change a trooper's role, or anything a plugin adds.
 */
class RequestPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->is_moderator || $this->user()->is_administrator;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization_ids = $this->requestableOrganizationIds();

        return [
            ...$this->extensionRules(),
            'trooper_id' => [
                'required',
                'integer',
                Rule::notIn([$this->user()->id]),
                Rule::exists(Trooper::class, Trooper::ID)
                    ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE),
            ],
            'membership_role' => [
                'nullable',
                Rule::in([
                    MembershipRole::MODERATOR->value,
                    MembershipRole::EVENT_COORDINATOR->value,
                    MembershipRole::MEMBER->value,
                ]),
            ],
            'moderated_organization_ids' => ['array', 'required_if:membership_role,moderator'],
            'moderated_organization_ids.*' => ['integer', Rule::in($organization_ids)],
            'extensions' => ['array'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trooper_id.required' => 'Pick the trooper this request is for.',
            'trooper_id.not_in' => 'You cannot request permission changes for yourself.',
            'trooper_id.exists' => 'The selected trooper does not exist (or is not active).',
            'moderated_organization_ids.required_if' => 'Pick an organization to moderate.',
            'moderated_organization_ids.*.in' => 'You can only pick organizations you moderate.',
        ];
    }

    /**
     * Cross-field checks. They only run once the field rules pass, so they can
     * rely on well-formed input.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void
            {
                if ($validator->errors()->isNotEmpty())
                {
                    return;
                }

                $this->validateSomethingRequested($validator);
                $this->validateRoleChange($validator);
                $this->validateModerationChange($validator);
                $this->validateExtensions($validator);
            },
        ];
    }

    private function validateSomethingRequested(Validator $validator): void
    {
        if ($this->filled('membership_role') || $this->extensionsRequestChange())
        {
            return;
        }

        $validator->errors()->add('membership_role', 'Choose a change to request.');
    }

    private function validateRoleChange(Validator $validator): void
    {
        $trooper = $this->targetTrooper();

        if ($trooper === null || !$this->filled('membership_role'))
        {
            return;
        }

        if ($trooper->membership_role->hasAdministratorAccess())
        {
            $validator->errors()->add(
                'membership_role',
                'Administrator and staff officer roles cannot be changed.'
            );
        }
        elseif ($this->input('membership_role') === MembershipRole::MEMBER->value
            && !in_array($trooper->membership_role, [MembershipRole::MODERATOR, MembershipRole::EVENT_COORDINATOR], true))
        {
            $validator->errors()->add('membership_role', 'This trooper is not a moderator or event coordinator.');
        }
        elseif ($this->input('membership_role') === MembershipRole::EVENT_COORDINATOR->value
            && $trooper->membership_role === MembershipRole::EVENT_COORDINATOR)
        {
            $validator->errors()->add('membership_role', 'This trooper is already an event coordinator.');
        }
    }

    /**
     * An existing moderator's request must actually change something.
     */
    private function validateModerationChange(Validator $validator): void
    {
        $trooper = $this->targetTrooper();

        if ($trooper?->membership_role !== MembershipRole::MODERATOR
            || $this->input('membership_role') !== MembershipRole::MODERATOR->value
            || $this->extensionsRequestChange())
        {
            return;
        }

        $changes = GetModerationChanges::call(
            requester: $this->user(),
            trooper: $trooper,
            organization_ids: $this->input('moderated_organization_ids', []),
        );

        if ($changes['add'] === [] && $changes['remove'] === [])
        {
            $validator->errors()->add(
                'moderated_organization_ids',
                'Change at least one organization.'
            );
        }
    }

    private function validateExtensions(Validator $validator): void
    {
        $trooper = $this->targetTrooper();

        if ($trooper === null)
        {
            return;
        }

        foreach ($this->extensions() as $key => $extension)
        {
            $extension->validate($validator, $trooper, $this->extensionInput($key));
        }
    }

    private function extensionsRequestChange(): bool
    {
        foreach ($this->extensions() as $key => $extension)
        {
            if ($extension->requestsChange($this->extensionInput($key)))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function extensionRules(): array
    {
        $rules = [];

        foreach ($this->extensions() as $key => $extension)
        {
            foreach ($extension->rules() as $field => $field_rules)
            {
                $rules["extensions.{$key}.{$field}"] = $field_rules;
            }
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function extensionInput(string $key): array
    {
        $input = $this->input("extensions.{$key}", []);

        return is_array($input) ? $input : [];
    }

    /**
     * @return array<string, \App\Contracts\PermissionRequestExtension>
     */
    private function extensions(): array
    {
        return app(PluginPermissionRequests::class)->all();
    }

    private function targetTrooper(): ?Trooper
    {
        return Trooper::find((int) $this->input('trooper_id'));
    }

    /**
     * @return array<int, int>
     */
    private function requestableOrganizationIds(): array
    {
        return GetRequestableOrganizations::call(requester: $this->user())
            ->map(fn (Organization $organization): int => $organization->id)
            ->all();
    }
}
