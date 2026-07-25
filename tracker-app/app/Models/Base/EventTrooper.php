<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Costume;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
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
 * @property int|null $event_shift_station_id
 * @property int $trooper_id
 * @property int|null $organization_id
 * @property int|null $costume_id
 * @property bool $is_attending_without_costume
 * @property array|null $costume_organization_ids
 * @property int|null $backup_costume_id
 * @property array|null $backup_costume_organization_ids
 * @property int|null $added_by_trooper_id
 * @property bool $is_handler
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
 * @property Costume|null $costume
 * @property EventShift $event_shift
 * @property EventShiftStation|null $event_shift_station
 * @property Organization|null $organization
 *
 * @package App\Models\Base
 */
class EventTrooper extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const EVENT_SHIFT_ID = 'event_shift_id';
    const EVENT_SHIFT_STATION_ID = 'event_shift_station_id';
    const TROOPER_ID = 'trooper_id';
    const ORGANIZATION_ID = 'organization_id';
    const COSTUME_ID = 'costume_id';
    const IS_ATTENDING_WITHOUT_COSTUME = 'is_attending_without_costume';
    const COSTUME_ORGANIZATION_IDS = 'costume_organization_ids';
    const BACKUP_COSTUME_ID = 'backup_costume_id';
    const BACKUP_COSTUME_ORGANIZATION_IDS = 'backup_costume_organization_ids';
    const ADDED_BY_TROOPER_ID = 'added_by_trooper_id';
    const IS_HANDLER = 'is_handler';
    const STATUS = 'status';
    const SIGNED_UP_AT = 'signed_up_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_event_troopers';

    protected $casts = [
        self::ID => 'int',
        self::EVENT_SHIFT_ID => 'int',
        self::EVENT_SHIFT_STATION_ID => 'int',
        self::TROOPER_ID => 'int',
        self::ORGANIZATION_ID => 'int',
        self::COSTUME_ID => 'int',
        self::IS_ATTENDING_WITHOUT_COSTUME => 'bool',
        self::COSTUME_ORGANIZATION_IDS => 'json',
        self::BACKUP_COSTUME_ID => 'int',
        self::BACKUP_COSTUME_ORGANIZATION_IDS => 'json',
        self::ADDED_BY_TROOPER_ID => 'int',
        self::IS_HANDLER => 'bool',
        self::SIGNED_UP_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::EVENT_SHIFT_ID,
        self::EVENT_SHIFT_STATION_ID,
        self::TROOPER_ID,
        self::ORGANIZATION_ID,
        self::COSTUME_ID,
        self::IS_ATTENDING_WITHOUT_COSTUME,
        self::COSTUME_ORGANIZATION_IDS,
        self::BACKUP_COSTUME_ID,
        self::BACKUP_COSTUME_ORGANIZATION_IDS,
        self::ADDED_BY_TROOPER_ID,
        self::IS_HANDLER,
        self::STATUS,
        self::SIGNED_UP_AT
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }

    public function costume(): BelongsTo
    {
        return $this->belongsTo(Costume::class);
    }

    public function event_shift(): BelongsTo
    {
        return $this->belongsTo(EventShift::class);
    }

    public function event_shift_station(): BelongsTo
    {
        return $this->belongsTo(EventShiftStation::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
