<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Event;
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
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Event $event
 * @property Collection|EventTrooper[] $event_troopers
 *
 * @package App\Models\Base
 */
class EventShift extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const EVENT_ID = 'event_id';
    const STARTS_AT = 'starts_at';
    const ENDS_AT = 'ends_at';
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
        self::STARTS_AT => 'datetime',
        self::ENDS_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::EVENT_ID,
        self::STARTS_AT,
        self::ENDS_AT
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }
}
