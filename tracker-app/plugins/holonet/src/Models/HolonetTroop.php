<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A troop copied from a linked tracker. It counts on the trooper's own service
 * record only, never in this tracker's reports, awards or club credit.
 *
 * @property int $id
 * @property int $holonet_link_id
 * @property string $remote_id
 * @property string $event_name
 * @property string|null $event_type
 * @property string|null $venue
 * @property Carbon $shift_starts_at
 * @property Carbon $shift_ends_at
 * @property string|null $costume_name
 * @property array<int, string>|null $clubs
 * @property bool $is_handler
 * @property-read HolonetLink $link
 */
class HolonetTroop extends Model
{
    public const ID = 'id';

    public const HOLONET_LINK_ID = 'holonet_link_id';

    public const REMOTE_ID = 'remote_id';

    public const EVENT_NAME = 'event_name';

    public const EVENT_TYPE = 'event_type';

    public const VENUE = 'venue';

    public const SHIFT_STARTS_AT = 'shift_starts_at';

    public const SHIFT_ENDS_AT = 'shift_ends_at';

    public const COSTUME_NAME = 'costume_name';

    public const CLUBS = 'clubs';

    public const IS_HANDLER = 'is_handler';

    protected $table = 'tt_trooper_holonet_troops';

    protected $fillable = [
        self::HOLONET_LINK_ID,
        self::REMOTE_ID,
        self::EVENT_NAME,
        self::EVENT_TYPE,
        self::VENUE,
        self::SHIFT_STARTS_AT,
        self::SHIFT_ENDS_AT,
        self::COSTUME_NAME,
        self::CLUBS,
        self::IS_HANDLER,
    ];

    protected $casts = [
        self::HOLONET_LINK_ID => 'int',
        self::SHIFT_STARTS_AT => 'datetime',
        self::SHIFT_ENDS_AT => 'datetime',
        self::CLUBS => 'array',
        self::IS_HANDLER => 'bool',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(HolonetLink::class, self::HOLONET_LINK_ID);
    }

    public function overlaps(Carbon $starts_at, Carbon $ends_at): bool
    {
        return $this->shift_starts_at->lt($ends_at) && $starts_at->lt($this->shift_ends_at);
    }

    public function hours(): float
    {
        return max(0, $this->shift_starts_at->diffInMinutes($this->shift_ends_at)) / 60;
    }
}
