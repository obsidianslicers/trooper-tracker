<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * An organization's forum settings: where its event threads go, where they are
 * archived, and the user groups the sync gives its active, reserve and retired
 * members.
 *
 * @property int $id
 * @property int $organization_id
 * @property ?int $event_forum_id
 * @property ?int $archive_forum_id
 * @property ?int $active_group_id
 * @property ?int $reserve_group_id
 * @property ?int $retired_group_id
 */
class OrganizationForum extends Model
{
    public const ORGANIZATION_ID = 'organization_id';

    public const EVENT_FORUM_ID = 'event_forum_id';

    public const ARCHIVE_FORUM_ID = 'archive_forum_id';

    public const ACTIVE_GROUP_ID = 'active_group_id';

    public const RESERVE_GROUP_ID = 'reserve_group_id';

    public const RETIRED_GROUP_ID = 'retired_group_id';

    public const SETTINGS = [
        self::EVENT_FORUM_ID,
        self::ARCHIVE_FORUM_ID,
        self::ACTIVE_GROUP_ID,
        self::RESERVE_GROUP_ID,
        self::RETIRED_GROUP_ID,
    ];

    public const GROUPS = [self::ACTIVE_GROUP_ID, self::RESERVE_GROUP_ID, self::RETIRED_GROUP_ID];

    protected $table = 'tt_xenforo_organization_forums';

    protected $fillable = [self::ORGANIZATION_ID, ...self::SETTINGS];

    protected $casts = [
        self::ORGANIZATION_ID => 'int',
        self::EVENT_FORUM_ID => 'int',
        self::ARCHIVE_FORUM_ID => 'int',
        self::ACTIVE_GROUP_ID => 'int',
        self::RESERVE_GROUP_ID => 'int',
        self::RETIRED_GROUP_ID => 'int',
    ];

    public static function forOrganization(?int $organization_id): ?self
    {
        return $organization_id === null
            ? null
            : static::query()->where(self::ORGANIZATION_ID, $organization_id)->first();
    }

    /**
     * Save an organization's settings. Empty values clear them.
     *
     * @param  array<string, mixed>  $values
     */
    public static function store(int $organization_id, array $values): self
    {
        $clean = [];

        foreach (array_intersect_key($values, array_flip(self::SETTINGS)) as $key => $value)
        {
            $clean[$key] = filled($value) ? (int) $value : null;
        }

        return static::query()->updateOrCreate([self::ORGANIZATION_ID => $organization_id], $clean);
    }

    /**
     * Settings keyed by organization ID, for every organization that has any.
     *
     * @return Collection<int, self>
     */
    public static function allByOrganization(): Collection
    {
        return static::query()->get()->keyBy(self::ORGANIZATION_ID);
    }

    /**
     * Every group ID the sync manages, across all organizations.
     *
     * @return Collection<int, int>
     */
    public static function managedGroupIds(): Collection
    {
        return static::query()
            ->get(self::GROUPS)
            ->flatMap(fn (self $forum): array => array_values($forum->only(self::GROUPS)))
            ->filter()
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
