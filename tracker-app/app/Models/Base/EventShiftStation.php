<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\EventShift;
use App\Models\EventTrooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EventShiftStation
 *
 * @property int $id
 * @property int $event_shift_id
 * @property string $name
 * @property int $troopers_allowed
 * @property int $sequence
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 *
 * @property EventShift $event_shift
 * @property Collection|EventTrooper[] $event_troopers
 *
 * @package App\Models\Base
 */
class EventShiftStation extends Model
{
    use SoftDeletes;

    const ID = 'id';
    const EVENT_SHIFT_ID = 'event_shift_id';
    const NAME = 'name';
    const TROOPERS_ALLOWED = 'troopers_allowed';
    const SEQUENCE = 'sequence';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';

    protected $table = 'tt_event_shift_stations';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_SHIFT_ID => 'int',
        self::TROOPERS_ALLOWED => 'int',
        self::SEQUENCE => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int',
    ];

    protected $fillable = [
        self::EVENT_SHIFT_ID,
        self::NAME,
        self::TROOPERS_ALLOWED,
        self::SEQUENCE,
    ];

    public function event_shift(): BelongsTo
    {
        return $this->belongsTo(EventShift::class);
    }

    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }
}
