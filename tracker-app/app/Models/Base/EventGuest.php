<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\EventShift;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EventGuest
 * 
 * @property int $id
 * @property int $event_shift_id
 * @property int $added_by_trooper_id
 * @property string $name
 * @property string $status
 * @property Carbon $signed_up_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Trooper $trooper
 * @property EventShift $event_shift
 *
 * @package App\Models\Base
 */
class EventGuest extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const EVENT_SHIFT_ID = 'event_shift_id';
    const ADDED_BY_TROOPER_ID = 'added_by_trooper_id';
    const NAME = 'name';
    const STATUS = 'status';
    const SIGNED_UP_AT = 'signed_up_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_event_guests';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_SHIFT_ID => 'int',
        self::ADDED_BY_TROOPER_ID => 'int',
        self::SIGNED_UP_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::EVENT_SHIFT_ID,
        self::ADDED_BY_TROOPER_ID,
        self::NAME,
        self::STATUS,
        self::SIGNED_UP_AT
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, \App\Models\EventGuest::ADDED_BY_TROOPER_ID);
    }

    public function event_shift(): BelongsTo
    {
        return $this->belongsTo(EventShift::class);
    }
}
