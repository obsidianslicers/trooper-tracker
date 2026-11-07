<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Models;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-time code handed to another tracker after a trooper allows a link.
 * The other tracker swaps it for an access key within a few minutes.
 *
 * @property int $id
 * @property int $trooper_id
 * @property string $tracker_url
 * @property string $code_hash
 * @property string $code_challenge
 * @property Carbon $expires_at
 * @property-read Trooper $trooper
 */
class HolonetCode extends Model
{
    public const ID = 'id';

    public const TROOPER_ID = 'trooper_id';

    public const TRACKER_URL = 'tracker_url';

    public const CODE_HASH = 'code_hash';

    public const CODE_CHALLENGE = 'code_challenge';

    public const EXPIRES_AT = 'expires_at';

    protected $table = 'tt_trooper_holonet_codes';

    protected $fillable = [
        self::TROOPER_ID,
        self::TRACKER_URL,
        self::CODE_HASH,
        self::CODE_CHALLENGE,
        self::EXPIRES_AT,
    ];

    protected $casts = [
        self::TROOPER_ID => 'int',
        self::EXPIRES_AT => 'datetime',
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
