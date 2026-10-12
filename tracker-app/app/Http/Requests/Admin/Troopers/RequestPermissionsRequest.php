<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Messages\Troopers\Queries\Permissions\GetRequestableForumGroups;
use App\Messages\Troopers\Queries\Permissions\GetRequestableOrganizations;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a moderator's request to change a trooper's role or forum groups.
 */
class RequestPermissionsRequest extends FormRequest
{
    private const GROUPS_TO_ADD = 'forum_group_ids_to_add';

    private const GROUPS_TO_REMOVE = 'forum_group_ids_to_remove';

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
        $group_ids = $this->requestableGroupIds();

        return [
            'trooper_id' => [
                'required',
                'integer',
                Rule::notIn([$this->user()->id]),
                Rule::exists(Trooper::class, Trooper::ID)
                    ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE),
            ],
            'membership_role' => [
                'nullable',
                Rule::in([MembershipRole::MODERATOR->value, MembershipRole::MEMBER->value]),
            ],
            'moderated_organization_ids' => ['array', 'required_if:membership_role,moderator'],
            'moderated_organization_ids.*' => ['integer', Rule::in($organization_ids)],
            self::GROUPS_TO_ADD => ['array'],
            self::GROUPS_TO_ADD.'.*' => ['integer', Rule::in($group_ids)],
            self::GROUPS_TO_REMOVE => ['array'],
            self::GROUPS_TO_REMOVE.'.*' => ['integer', Rule::in($group_ids)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $group_message = 'One of the selected forum groups cannot be requested.';

        return [
            'trooper_id.required' => 'Pick the trooper this request is for.',
            'trooper_id.not_in' => 'You cannot request permission changes for yourself.',
            'trooper_id.exists' => 'The selected trooper does not exist (or is not active).',
            'moderated_organization_ids.required_if' => 'Pick an organization to moderate.',
            'moderated_organization_ids.*.in' => 'You can only pick organizations you moderate.',
            self::GROUPS_TO_ADD.'.*.in' => $group_message,
            self::GROUPS_TO_REMOVE.'.*.in' => $group_message,
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
                $this->validateForumGroups($validator);
            },
        ];
    }

    private function validateSomethingRequested(Validator $validator): void
    {
        if ($this->filled('membership_role') || $this->hasForumGroups())
        {
            return;
        }

        $validator->errors()->add('membership_role', 'Choose a role change or a forum group.');
    }

    private function validateRoleChange(Validator $validator): void
    {
        $trooper = $this->targetTrooper();

        if ($trooper === null || !$this->filled('membership_role'))
        {
            return;
        }

        if ($trooper->membership_role === MembershipRole::ADMINISTRATOR)
        {
            $validator->errors()->add('membership_role', 'Administrator roles cannot be changed.');
        }
        elseif ($this->input('membership_role') === MembershipRole::MEMBER->value
            && $trooper->membership_role !== MembershipRole::MODERATOR)
        {
            $validator->errors()->add('membership_role', 'This trooper is not a moderator.');
        }
    }

    private function validateForumGroups(Validator $validator): void
    {
        if (!$this->hasForumGroups())
        {
            return;
        }

        $overlap = array_intersect(
            $this->input(self::GROUPS_TO_ADD, []),
            $this->input(self::GROUPS_TO_REMOVE, [])
        );

        if ($overlap !== [])
        {
            $validator->errors()->add(self::GROUPS_TO_ADD, 'A group cannot be added and removed.');
        }

        $trooper = $this->targetTrooper();
        $xenforo = app(XenforoService::class);

        if ($trooper !== null && $xenforo->resolve_user_id_for_trooper($trooper->id) === null)
        {
            $validator->errors()->add(self::GROUPS_TO_ADD, 'This trooper has no forum account.');
        }
    }

    private function hasForumGroups(): bool
    {
        return !empty($this->input(self::GROUPS_TO_ADD))
            || !empty($this->input(self::GROUPS_TO_REMOVE));
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

    /**
     * @return array<int, int>
     */
    private function requestableGroupIds(): array
    {
        if (!$this->hasForumGroups())
        {
            return [];
        }

        return GetRequestableForumGroups::call()->pluck('id')->all();
    }
}
