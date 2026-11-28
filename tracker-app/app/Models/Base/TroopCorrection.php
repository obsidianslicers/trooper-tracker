<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TroopCorrection
 * 
 * @property int $id
 * @property int $trooper_id
 * @property int $event_id
 * @property int $event_trooper_id
 * @property string|null $previous_status
 * @property string $details
 * @property string|null $proof_url
 * @property int|null $coordinator_trooper_id
 * @property string $status
 * @property int|null $decided_by_trooper_id
 * @property Carbon|null $decided_at
 * @property Carbon|null $reminded_at
 * @property Carbon|null $coordinator_denied_at
 * @property Carbon|null $escalated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Trooper $trooper
 * @property Event $event
 * @property EventTrooper $event_trooper
 *
 * @package App\Models\Base
 */
class TroopCorrection extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const EVENT_ID = 'event_id';
    const EVENT_TROOPER_ID = 'event_trooper_id';
    const PREVIOUS_STATUS = 'previous_status';
    const DETAILS = 'details';
    const PROOF_URL = 'proof_url';
    const COORDINATOR_TROOPER_ID = 'coordinator_trooper_id';
    const STATUS = 'status';
    const DECIDED_BY_TROOPER_ID = 'decided_by_trooper_id';
    const DECIDED_AT = 'decided_at';
    const REMINDED_AT = 'reminded_at';
    const COORDINATOR_DENIED_AT = 'coordinator_denied_at';
    const ESCALATED_AT = 'escalated_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_troop_corrections';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::EVENT_ID => 'int',
        self::EVENT_TROOPER_ID => 'int',
        self::COORDINATOR_TROOPER_ID => 'int',
        self::DECIDED_BY_TROOPER_ID => 'int',
        self::DECIDED_AT => 'datetime',
        self::REMINDED_AT => 'datetime',
        self::COORDINATOR_DENIED_AT => 'datetime',
        self::ESCALATED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::EVENT_ID,
        self::EVENT_TROOPER_ID,
        self::PREVIOUS_STATUS,
        self::DETAILS,
        self::PROOF_URL,
        self::COORDINATOR_TROOPER_ID,
        self::STATUS,
        self::DECIDED_BY_TROOPER_ID,
        self::DECIDED_AT,
        self::REMINDED_AT,
        self::COORDINATOR_DENIED_AT,
        self::ESCALATED_AT
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function event_trooper(): BelongsTo
    {
        return $this->belongsTo(EventTrooper::class);
    }
}
