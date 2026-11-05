<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The forum groups a permission request asks to add or remove. Titles are
 * snapshotted so the request still reads correctly if the forum renames or
 * deletes a group later.
 *
 * @property int $id
 * @property int $permission_request_id
 * @property array<int, array{id:int,title:string}>|null $groups_to_add
 * @property array<int, array{id:int,title:string}>|null $groups_to_remove
 */
class PermissionRequestGroups extends Model
{
    public const PERMISSION_REQUEST_ID = 'permission_request_id';

    public const GROUPS_TO_ADD = 'groups_to_add';

    public const GROUPS_TO_REMOVE = 'groups_to_remove';

    protected $table = 'tt_xenforo_permission_request_groups';

    protected $fillable = [self::PERMISSION_REQUEST_ID, self::GROUPS_TO_ADD, self::GROUPS_TO_REMOVE];

    protected $casts = [
        self::PERMISSION_REQUEST_ID => 'int',
        self::GROUPS_TO_ADD => 'array',
        self::GROUPS_TO_REMOVE => 'array',
    ];

    public static function forRequest(int $permission_request_id): ?self
    {
        return static::query()->where(self::PERMISSION_REQUEST_ID, $permission_request_id)->first();
    }

    /**
     * @return array<int, int>
     */
    public function idsToAdd(): array
    {
        return self::ids($this->groups_to_add);
    }

    /**
     * @return array<int, int>
     */
    public function idsToRemove(): array
    {
        return self::ids($this->groups_to_remove);
    }

    /**
     * @param  array<int, array{id:int,title:string}>|null  $groups
     * @return array<int, int>
     */
    private static function ids(?array $groups): array
    {
        return array_map(fn (array $group): int => (int) $group['id'], $groups ?? []);
    }
}
