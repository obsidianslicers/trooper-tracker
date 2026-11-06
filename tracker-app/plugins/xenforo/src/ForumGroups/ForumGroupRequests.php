<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\ForumGroups;

use App\Contracts\PermissionRequestExtension;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use TroopTracker\Plugins\Xenforo\Models\PermissionRequestGroups;

/**
 * Lets a permission request add or remove a trooper's forum user groups, and
 * applies them when an administrator approves it.
 */
final class ForumGroupRequests implements PermissionRequestExtension
{
    public const KEY = 'xenforo';

    private const TO_ADD = 'groups_to_add';

    private const TO_REMOVE = 'groups_to_remove';

    public function __construct(private readonly XenforoForumGroups $forum_groups) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function formSection(Trooper $trooper): string
    {
        if (!$this->forum_groups->isAvailable())
        {
            return '';
        }

        $groups = $this->forum_groups->requestableGroups();
        $current_ids = $this->forum_groups->currentGroupIds($trooper) ?? [];
        $is_current = fn (array $group): bool => in_array($group['id'], $current_ids, true);

        return View::make('xenforo::admin.permission-request-groups', [
            'has_account' => $this->forum_groups->hasAccount($trooper),
            'addable' => $groups->reject($is_current),
            'current' => $groups->filter($is_current),
            'prefix' => 'extensions['.self::KEY.']',
        ])->render();
    }

    public function rules(): array
    {
        $group_ids = fn (): array => $this->forum_groups->requestableGroups()->pluck('id')->all();

        return [
            self::TO_ADD => ['array'],
            self::TO_ADD.'.*' => ['integer', Rule::in($group_ids())],
            self::TO_REMOVE => ['array'],
            self::TO_REMOVE.'.*' => ['integer', Rule::in($group_ids())],
        ];
    }

    public function validate(Validator $validator, Trooper $trooper, array $input): void
    {
        if (!$this->requestsChange($input))
        {
            return;
        }

        $field = 'extensions.'.self::KEY.'.'.self::TO_ADD;
        $to_add = $this->ids($input, self::TO_ADD);

        if (array_intersect($to_add, $this->ids($input, self::TO_REMOVE)) !== [])
        {
            $validator->errors()->add($field, 'A forum group cannot be added and removed.');
        }

        if (!$this->forum_groups->hasAccount($trooper))
        {
            $validator->errors()->add($field, 'This trooper has no forum account.');
        }
    }

    public function requestsChange(array $input): bool
    {
        return $this->ids($input, self::TO_ADD) !== []
            || $this->ids($input, self::TO_REMOVE) !== [];
    }

    public function store(PermissionRequest $permission_request, array $input): void
    {
        if (!$this->requestsChange($input))
        {
            return;
        }

        $groups = $this->forum_groups->requestableGroups();

        PermissionRequestGroups::create([
            PermissionRequestGroups::PERMISSION_REQUEST_ID => $permission_request->id,
            PermissionRequestGroups::GROUPS_TO_ADD => $this->pick($groups, $input, self::TO_ADD),
            PermissionRequestGroups::GROUPS_TO_REMOVE => $this
                ->pick($groups, $input, self::TO_REMOVE),
        ]);
    }

    public function summary(PermissionRequest $permission_request): array
    {
        $groups = PermissionRequestGroups::forRequest($permission_request->id);

        return [
            'Add Forum Groups' => $this->titles($groups?->groups_to_add),
            'Remove Forum Groups' => $this->titles($groups?->groups_to_remove),
        ];
    }

    public function approve(PermissionRequest $permission_request): void
    {
        $groups = PermissionRequestGroups::forRequest($permission_request->id);

        if ($groups === null)
        {
            return;
        }

        $this->forum_groups->updateGroups(
            $permission_request->trooper,
            $groups->idsToAdd(),
            $groups->idsToRemove(),
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, int>
     */
    private function ids(array $input, string $field): array
    {
        $ids = $input[$field] ?? [];

        return is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    }

    /**
     * @param  Collection<int, array{id:int,title:string,banner_image_url:?string}>  $groups
     * @param  array<string, mixed>  $input
     * @return array<int, array{id:int,title:string}>
     */
    private function pick(Collection $groups, array $input, string $field): array
    {
        $ids = $this->ids($input, $field);

        return $groups
            ->filter(fn (array $group): bool => in_array($group['id'], $ids, true))
            ->map(fn (array $group): array => ['id' => $group['id'], 'title' => $group['title']])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id:int,title:string}>|null  $groups
     */
    private function titles(?array $groups): string
    {
        return collect($groups ?? [])->pluck('title')->implode(', ');
    }
}
