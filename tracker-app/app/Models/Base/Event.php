<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\EventNotification;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventUpload;
use App\Models\Organization;
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
 * @property int $primary_organization_id
 * @property string $name
 * @property string $type
 * @property string $status
 * @property Carbon|null $create_notifications_sent_at
 * @property Carbon|null $cancel_notifications_sent_at
 * @property bool $create_forum_thread
 * @property int|null $thread_id
 * @property int|null $post_id
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int|null $shifts_allowed
 * @property int|null $troopers_allowed
 * @property int|null $handlers_allowed
 * @property int|null $friends_allowed
 * @property bool $tentative_signups_allowed
 * @property int $charity_direct_funds
 * @property int $charity_indirect_funds
 * @property string|null $charity_name
 * @property int|null $charity_hours
 * @property string|null $charity_notes
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $venue
 * @property string|null $venue_address
 * @property string|null $venue_city
 * @property string|null $venue_state
 * @property string|null $venue_zip
 * @property string|null $venue_country
 * @property Carbon|null $event_start
 * @property Carbon|null $event_end
 * @property string|null $event_website
 * @property int|null $expected_attendees
 * @property int|null $requested_number_characters
 * @property string|null $requested_character_types
 * @property bool $secure_staging_area
 * @property bool $allow_blasters
 * @property bool $allow_props
 * @property bool $parking_available
 * @property bool $accessible
 * @property string|null $amenities
 * @property string|null $referred_by
 * @property string|null $source
 * @property string|null $comments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Organization $organization
 * @property Collection|EventNotification[] $event_notifications
 * @property Collection|Organization[] $organizations
 * @property Collection|EventShift[] $event_shifts
 * @property Collection|EventUpload[] $event_uploads
 *
 * @package App\Models\Base
 */
class Event extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const ORGANIZATION_ID = 'organization_id';
    const PRIMARY_ORGANIZATION_ID = 'primary_organization_id';
    const NAME = 'name';
    const TYPE = 'type';
    const STATUS = 'status';
    const CREATE_NOTIFICATIONS_SENT_AT = 'create_notifications_sent_at';
    const CANCEL_NOTIFICATIONS_SENT_AT = 'cancel_notifications_sent_at';
    const CREATE_FORUM_THREAD = 'create_forum_thread';
    const THREAD_ID = 'thread_id';
    const POST_ID = 'post_id';
    const LATITUDE = 'latitude';
    const LONGITUDE = 'longitude';
    const SHIFTS_ALLOWED = 'shifts_allowed';
    const TROOPERS_ALLOWED = 'troopers_allowed';
    const HANDLERS_ALLOWED = 'handlers_allowed';
    const FRIENDS_ALLOWED = 'friends_allowed';
    const TENTATIVE_SIGNUPS_ALLOWED = 'tentative_signups_allowed';
    const CHARITY_DIRECT_FUNDS = 'charity_direct_funds';
    const CHARITY_INDIRECT_FUNDS = 'charity_indirect_funds';
    const CHARITY_NAME = 'charity_name';
    const CHARITY_HOURS = 'charity_hours';
    const CHARITY_NOTES = 'charity_notes';
    const CONTACT_NAME = 'contact_name';
    const CONTACT_PHONE = 'contact_phone';
    const CONTACT_EMAIL = 'contact_email';
    const VENUE = 'venue';
    const VENUE_ADDRESS = 'venue_address';
    const VENUE_CITY = 'venue_city';
    const VENUE_STATE = 'venue_state';
    const VENUE_ZIP = 'venue_zip';
    const VENUE_COUNTRY = 'venue_country';
    const EVENT_START = 'event_start';
    const EVENT_END = 'event_end';
    const EVENT_WEBSITE = 'event_website';
    const EXPECTED_ATTENDEES = 'expected_attendees';
    const REQUESTED_NUMBER_CHARACTERS = 'requested_number_characters';
    const REQUESTED_CHARACTER_TYPES = 'requested_character_types';
    const SECURE_STAGING_AREA = 'secure_staging_area';
    const ALLOW_BLASTERS = 'allow_blasters';
    const ALLOW_PROPS = 'allow_props';
    const PARKING_AVAILABLE = 'parking_available';
    const ACCESSIBLE = 'accessible';
    const AMENITIES = 'amenities';
    const REFERRED_BY = 'referred_by';
    const SOURCE = 'source';
    const COMMENTS = 'comments';
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
        self::PRIMARY_ORGANIZATION_ID => 'int',
        self::CREATE_NOTIFICATIONS_SENT_AT => 'datetime',
        self::CANCEL_NOTIFICATIONS_SENT_AT => 'datetime',
        self::CREATE_FORUM_THREAD => 'bool',
        self::THREAD_ID => 'int',
        self::POST_ID => 'int',
        self::LATITUDE => 'float',
        self::LONGITUDE => 'float',
        self::SHIFTS_ALLOWED => 'int',
        self::TROOPERS_ALLOWED => 'int',
        self::HANDLERS_ALLOWED => 'int',
        self::FRIENDS_ALLOWED => 'int',
        self::TENTATIVE_SIGNUPS_ALLOWED => 'bool',
        self::CHARITY_DIRECT_FUNDS => 'int',
        self::CHARITY_INDIRECT_FUNDS => 'int',
        self::CHARITY_HOURS => 'int',
        self::EVENT_START => 'datetime',
        self::EVENT_END => 'datetime',
        self::EXPECTED_ATTENDEES => 'int',
        self::REQUESTED_NUMBER_CHARACTERS => 'int',
        self::SECURE_STAGING_AREA => 'bool',
        self::ALLOW_BLASTERS => 'bool',
        self::ALLOW_PROPS => 'bool',
        self::PARKING_AVAILABLE => 'bool',
        self::ACCESSIBLE => 'bool',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::ORGANIZATION_ID,
        self::PRIMARY_ORGANIZATION_ID,
        self::NAME,
        self::TYPE,
        self::STATUS,
        self::CREATE_NOTIFICATIONS_SENT_AT,
        self::CANCEL_NOTIFICATIONS_SENT_AT,
        self::CREATE_FORUM_THREAD,
        self::THREAD_ID,
        self::POST_ID,
        self::LATITUDE,
        self::LONGITUDE,
        self::SHIFTS_ALLOWED,
        self::TROOPERS_ALLOWED,
        self::HANDLERS_ALLOWED,
        self::FRIENDS_ALLOWED,
        self::TENTATIVE_SIGNUPS_ALLOWED,
        self::CHARITY_DIRECT_FUNDS,
        self::CHARITY_INDIRECT_FUNDS,
        self::CHARITY_NAME,
        self::CHARITY_HOURS,
        self::CHARITY_NOTES,
        self::CONTACT_NAME,
        self::CONTACT_PHONE,
        self::CONTACT_EMAIL,
        self::VENUE,
        self::VENUE_ADDRESS,
        self::VENUE_CITY,
        self::VENUE_STATE,
        self::VENUE_ZIP,
        self::VENUE_COUNTRY,
        self::EVENT_START,
        self::EVENT_END,
        self::EVENT_WEBSITE,
        self::EXPECTED_ATTENDEES,
        self::REQUESTED_NUMBER_CHARACTERS,
        self::REQUESTED_CHARACTER_TYPES,
        self::SECURE_STAGING_AREA,
        self::ALLOW_BLASTERS,
        self::ALLOW_PROPS,
        self::PARKING_AVAILABLE,
        self::ACCESSIBLE,
        self::AMENITIES,
        self::REFERRED_BY,
        self::SOURCE,
        self::COMMENTS
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, \App\Models\Event::PRIMARY_ORGANIZATION_ID);
    }

    public function event_notifications(): HasMany
    {
        return $this->hasMany(EventNotification::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'tt_event_organizations')
                    ->withPivot(EventOrganization::ID, EventOrganization::CAN_ATTEND, EventOrganization::TROOPERS_ALLOWED, EventOrganization::HANDLERS_ALLOWED, EventOrganization::DELETED_AT, EventOrganization::CREATED_ID, EventOrganization::UPDATED_ID, EventOrganization::DELETED_ID)
                    ->withTimestamps();
    }

    public function event_shifts(): HasMany
    {
        return $this->hasMany(EventShift::class);
    }

    public function event_uploads(): HasMany
    {
        return $this->hasMany(EventUpload::class);
    }
}
