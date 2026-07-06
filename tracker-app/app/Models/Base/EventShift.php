<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EventShift
 * 
 * @property int $id
 * @property int $event_id
 * @property string $status
 * @property Carbon $shift_starts_at
 * @property Carbon $shift_ends_at
 * @property Carbon|null $last_notified_at
 * @property int $charity_direct_funds
 * @property int $charity_indirect_funds
 * @property string|null $charity_name
 * @property int|null $charity_hours
 * @property string|null $charity_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Event $event
 * @property Collection|EventGuest[] $event_guests
 * @property Collection|EventShiftStation[] $event_shift_stations
 * @property Collection|EventTrooper[] $event_troopers
 *
 * @package App\Models\Base
 */
class EventShift extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const EVENT_ID = 'event_id';
    const STATUS = 'status';
    const SHIFT_STARTS_AT = 'shift_starts_at';
    const SHIFT_ENDS_AT = 'shift_ends_at';
    const LAST_NOTIFIED_AT = 'last_notified_at';
    const CHARITY_DIRECT_FUNDS = 'charity_direct_funds';
    const CHARITY_INDIRECT_FUNDS = 'charity_indirect_funds';
    const CHARITY_NAME = 'charity_name';
    const CHARITY_HOURS = 'charity_hours';
    const CHARITY_NOTES = 'charity_notes';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_event_shifts';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_ID => 'int',
        self::SHIFT_STARTS_AT => 'datetime',
        self::SHIFT_ENDS_AT => 'datetime',
        self::LAST_NOTIFIED_AT => 'datetime',
        self::CHARITY_DIRECT_FUNDS => 'int',
        self::CHARITY_INDIRECT_FUNDS => 'int',
        self::CHARITY_HOURS => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::EVENT_ID,
        self::STATUS,
        self::SHIFT_STARTS_AT,
        self::SHIFT_ENDS_AT,
        self::LAST_NOTIFIED_AT,
        self::CHARITY_DIRECT_FUNDS,
        self::CHARITY_INDIRECT_FUNDS,
        self::CHARITY_NAME,
        self::CHARITY_HOURS,
        self::CHARITY_NOTES
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function event_guests(): HasMany
    {
        return $this->hasMany(EventGuest::class);
    }

    public function event_shift_stations(): HasMany
    {
        return $this->hasMany(EventShiftStation::class);
    }

    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }
}
