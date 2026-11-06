<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Models;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Access a trooper gave another tracker to read their troop history here. Only
 * a hash of the access key is kept.
 *
 * @property int $id
 * @property int $trooper_id
 * @property string $tracker_url
 * @property string $tracker_name
 * @property string $token_hash
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property-read Trooper $trooper
 */
class TrackerGrant extends Model
{
    public const ID = 'id';

    public const TROOPER_ID = 'trooper_id';

    public const TRACKER_URL = 'tracker_url';

    public const TRACKER_NAME = 'tracker_name';

    public const TOKEN_HASH = 'token_hash';

    public const LAST_USED_AT = 'last_used_at';

    protected $table = 'tt_trooper_tracker_grants';

    protected $fillable = [
        self::TROOPER_ID,
        self::TRACKER_URL,
        self::TRACKER_NAME,
        self::TOKEN_HASH,
        self::LAST_USED_AT,
    ];

    protected $hidden = [self::TOKEN_HASH];

    protected $casts = [
        self::TROOPER_ID => 'int',
        self::LAST_USED_AT => 'datetime',
    ];

    public static function findByToken(string $token): ?self
    {
        if ($token === '')
        {
            return null;
        }

        return static::query()->where(self::TOKEN_HASH, hash('sha256', $token))->first();
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
