<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\EventShift;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EventTrooper
 * 
 * @property int $id
 * @property int $event_shift_id
 * @property int $trooper_id
 * @property int|null $costume_id
 * @property int|null $backup_costume_id
 * @property int|null $added_by_trooper_id
 * @property string $status
 * @property Carbon $signed_up_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * @property int|null $is_handler
 * 
 * @property Trooper $trooper
 * @property OrganizationCostume|null $organization_costume
 * @property EventShift $event_shift
 *
 * @package App\Models\Base
 */
class EventTrooper extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const EVENT_SHIFT_ID = 'event_shift_id';
    const TROOPER_ID = 'trooper_id';
    const COSTUME_ID = 'costume_id';
    const BACKUP_COSTUME_ID = 'backup_costume_id';
    const ADDED_BY_TROOPER_ID = 'added_by_trooper_id';
    const STATUS = 'status';
    const SIGNED_UP_AT = 'signed_up_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    const IS_HANDLER = 'is_handler';
    protected $table = 'tt_event_troopers';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_SHIFT_ID => 'int',
        self::TROOPER_ID => 'int',
        self::COSTUME_ID => 'int',
        self::BACKUP_COSTUME_ID => 'int',
        self::ADDED_BY_TROOPER_ID => 'int',
        self::SIGNED_UP_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int',
        self::IS_HANDLER => 'int'
    ];

    protected $fillable = [
        self::EVENT_SHIFT_ID,
        self::TROOPER_ID,
        self::COSTUME_ID,
        self::BACKUP_COSTUME_ID,
        self::ADDED_BY_TROOPER_ID,
        self::STATUS,
        self::SIGNED_UP_AT,
        self::IS_HANDLER
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }

    public function organization_costume(): BelongsTo
    {
        return $this->belongsTo(OrganizationCostume::class, \App\Models\EventTrooper::COSTUME_ID);
    }

    public function event_shift(): BelongsTo
    {
        return $this->belongsTo(EventShift::class);
    }
}
