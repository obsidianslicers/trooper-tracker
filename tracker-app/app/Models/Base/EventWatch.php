<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Event;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EventWatch
 * 
 * @property int $id
 * @property int $event_id
 * @property int $trooper_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Event $event
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class EventWatch extends Model
{
    const ID = 'id';
    const EVENT_ID = 'event_id';
    const TROOPER_ID = 'trooper_id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected $table = 'tt_event_watches';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_ID => 'int',
        self::TROOPER_ID => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime'
    ];

    protected $fillable = [
        self::EVENT_ID,
        self::TROOPER_ID
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
