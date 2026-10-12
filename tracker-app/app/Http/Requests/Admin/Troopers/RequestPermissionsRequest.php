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
    public function authorize(): bool
    {
        return $this->user()->is_moderator || $this->user()->is_administrator;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'trooper_id' => [
                'required',
                Rule::notIn([$this->user()->id]),
                Rule::exists(Trooper::class, Trooper::ID)
                    ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE),
            ],
            'membership_role' => [
                'nullable',
                Rule::in([MembershipRole::MODERATOR->value, MembershipRole::MEMBER->value]),
            ],
            'moderated_organization_ids' => ['array', 'required_if:membership_role,moderator'],
            'moderated_organization_ids.*' => ['integer', Rule::in($this->requestableOrganizationIds())],
            'forum_group_ids_to_add' => ['array'],
            'forum_group_ids_to_add.*' => ['integer', Rule::in($this->requestableGroupIds())],
            'forum_group_ids_to_remove' => ['array'],
            'forum_group_ids_to_remove.*' => ['integer', Rule::in($this->requestableGroupIds())],
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
            'moderated_organization_ids.required_if' => 'Pick at least one organization for them to moderate.',
            'moderated_organization_ids.*.in' => 'You can only request moderator rights for organizations you moderate.',
            'forum_group_ids_to_add.*.in' => 'One of the selected forum groups cannot be requested.',
            'forum_group_ids_to_remove.*.in' => 'One of the selected forum groups cannot be requested.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateSomethingRequested($validator),
            fn (Validator $validator) => $this->validateRoleChange($validator),
            fn (Validator $validator) => $this->validateForumGroups($validator),
        ];
    }

    private function validateSomethingRequested(Validator $validator): void
    {
        if ($this->filled('membership_role') || $this->hasForumGroups())
        {
            return;
        }

        $validator->errors()->add('membership_role', 'Choose a role change or at least one forum group.');
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
            $validator->errors()->add('membership_role', 'Administrator roles cannot be changed with a request.');
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
            $this->input('forum_group_ids_to_add', []),
            $this->input('forum_group_ids_to_remove', [])
        );

        if ($overlap !== [])
        {
            $validator->errors()->add('forum_group_ids_to_add', 'A forum group cannot be both added and removed.');
        }

        $trooper = $this->targetTrooper();

        if ($trooper !== null && app(XenforoService::class)->resolve_user_id_for_trooper($trooper->id) === null)
        {
            $validator->errors()->add('forum_group_ids_to_add', 'This trooper has no linked forum account.');
        }
    }

    private function hasForumGroups(): bool
    {
        return !empty($this->input('forum_group_ids_to_add')) || !empty($this->input('forum_group_ids_to_remove'));
    }

    private function targetTrooper(): ?Trooper
    {
        return Trooper::find($this->input('trooper_id'));
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
