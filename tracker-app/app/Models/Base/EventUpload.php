<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Event;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EventUpload
 * 
 * @property int $id
 * @property int $event_id
 * @property int $trooper_id
 * @property string $filename
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Event $event
 * @property Trooper $trooper
 * @property Collection|Trooper[] $troopers
 *
 * @package App\Models\Base
 */
class EventUpload extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const EVENT_ID = 'event_id';
    const TROOPER_ID = 'trooper_id';
    const FILENAME = 'filename';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_event_uploads';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_ID => 'int',
        self::TROOPER_ID => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::EVENT_ID,
        self::TROOPER_ID,
        self::FILENAME
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }

    public function troopers(): BelongsToMany
    {
        return $this->belongsToMany(Trooper::class, 'tt_event_upload_troopers')
                    ->withPivot(EventUploadTrooper::ID, EventUploadTrooper::DELETED_AT, EventUploadTrooper::CREATED_ID, EventUploadTrooper::UPDATED_ID, EventUploadTrooper::DELETED_ID)
                    ->withTimestamps();
    }
}
