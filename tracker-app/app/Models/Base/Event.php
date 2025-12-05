<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\EventOrganization;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\EventVenue;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Event
 * 
 * @property int $id
 * @property int $organization_id
 * @property int $event_venue_id
 * @property int|null $main_event_id
 * @property bool $is_shift
 * @property string $name
 * @property string $type
 * @property string $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $limit_organizations
 * @property int|null $troopers_allowed
 * @property int|null $handlers_allowed
 * @property int $charity_direct_funds
 * @property int $charity_indirect_funds
 * @property string|null $charity_name
 * @property int|null $charity_hours
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property EventVenue $event_venue
 * @property \App\Models\Event|null $event
 * @property Organization $organization
 * @property Collection|Organization[] $organizations
 * @property Collection|Trooper[] $troopers
 * @property Collection|EventUpload[] $event_uploads
 * @property Collection|\App\Models\Event[] $events
 *
 * @package App\Models\Base
 */
class Event extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const ORGANIZATION_ID = 'organization_id';
    const EVENT_VENUE_ID = 'event_venue_id';
    const MAIN_EVENT_ID = 'main_event_id';
    const IS_SHIFT = 'is_shift';
    const NAME = 'name';
    const TYPE = 'type';
    const STATUS = 'status';
    const STARTS_AT = 'starts_at';
    const ENDS_AT = 'ends_at';
    const LIMIT_ORGANIZATIONS = 'limit_organizations';
    const TROOPERS_ALLOWED = 'troopers_allowed';
    const HANDLERS_ALLOWED = 'handlers_allowed';
    const CHARITY_DIRECT_FUNDS = 'charity_direct_funds';
    const CHARITY_INDIRECT_FUNDS = 'charity_indirect_funds';
    const CHARITY_NAME = 'charity_name';
    const CHARITY_HOURS = 'charity_hours';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_events';

    protected $casts = [
        self::ID => 'int',
        self::ORGANIZATION_ID => 'int',
        self::EVENT_VENUE_ID => 'int',
        self::MAIN_EVENT_ID => 'int',
        self::IS_SHIFT => 'bool',
        self::STARTS_AT => 'datetime',
        self::ENDS_AT => 'datetime',
        self::LIMIT_ORGANIZATIONS => 'bool',
        self::TROOPERS_ALLOWED => 'int',
        self::HANDLERS_ALLOWED => 'int',
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
        self::ORGANIZATION_ID,
        self::EVENT_VENUE_ID,
        self::MAIN_EVENT_ID,
        self::IS_SHIFT,
        self::NAME,
        self::TYPE,
        self::STATUS,
        self::STARTS_AT,
        self::ENDS_AT,
        self::LIMIT_ORGANIZATIONS,
        self::TROOPERS_ALLOWED,
        self::HANDLERS_ALLOWED,
        self::CHARITY_DIRECT_FUNDS,
        self::CHARITY_INDIRECT_FUNDS,
        self::CHARITY_NAME,
        self::CHARITY_HOURS
    ];

    public function event_venue(): BelongsTo
    {
        return $this->belongsTo(EventVenue::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Event::class, \App\Models\Event::MAIN_EVENT_ID);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'tt_event_organizations')
                    ->withPivot(EventOrganization::ID, EventOrganization::CAN_ATTEND, EventOrganization::TROOPERS_ALLOWED, EventOrganization::HANDLERS_ALLOWED, EventOrganization::DELETED_AT, EventOrganization::CREATED_ID, EventOrganization::UPDATED_ID, EventOrganization::DELETED_ID)
                    ->withTimestamps();
    }

    public function troopers(): BelongsToMany
    {
        return $this->belongsToMany(Trooper::class, 'tt_event_troopers')
                    ->withPivot(EventTrooper::ID, EventTrooper::COSTUME_ID, EventTrooper::BACKUP_COSTUME_ID, EventTrooper::ADDED_BY_TROOPER_ID, EventTrooper::STATUS, EventTrooper::DELETED_AT, EventTrooper::CREATED_ID, EventTrooper::UPDATED_ID, EventTrooper::DELETED_ID)
                    ->withTimestamps();
    }

    public function event_uploads(): HasMany
    {
        return $this->hasMany(EventUpload::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(\App\Models\Event::class, \App\Models\Event::MAIN_EVENT_ID);
    }
}
