<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Models;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A trooper's link from this tracker to their account on another tracker.
 *
 * @property int $id
 * @property int $trooper_id
 * @property string $tracker_url
 * @property string $tracker_name
 * @property string|null $remote_display_name
 * @property string $token
 * @property string $status
 * @property Carbon|null $refreshed_at
 * @property string|null $last_error
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Trooper $trooper
 * @property-read Collection<int, LinkedTroop> $troops
 */
class TrackerLink extends Model
{
    public const ID = 'id';

    public const TROOPER_ID = 'trooper_id';

    public const TRACKER_URL = 'tracker_url';

    public const TRACKER_NAME = 'tracker_name';

    public const REMOTE_DISPLAY_NAME = 'remote_display_name';

    public const TOKEN = 'token';

    public const STATUS = 'status';

    public const REFRESHED_AT = 'refreshed_at';

    public const LAST_ERROR = 'last_error';

    public const STATUS_ACTIVE = 'active';

    /** The other tracker no longer accepts this link. */
    public const STATUS_REVOKED = 'revoked';

    protected $table = 'tt_trooper_tracker_links';

    protected $fillable = [
        self::TROOPER_ID,
        self::TRACKER_URL,
        self::TRACKER_NAME,
        self::REMOTE_DISPLAY_NAME,
        self::TOKEN,
        self::STATUS,
        self::REFRESHED_AT,
        self::LAST_ERROR,
    ];

    protected $hidden = [self::TOKEN];

    protected $casts = [
        self::TROOPER_ID => 'int',
        self::TOKEN => 'encrypted',
        self::REFRESHED_AT => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }

    public function troops(): HasMany
    {
        return $this->hasMany(LinkedTroop::class, LinkedTroop::TROOPER_TRACKER_LINK_ID);
    }
}
