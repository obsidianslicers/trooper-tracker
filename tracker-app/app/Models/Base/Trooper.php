<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\EventGuest;
use App\Models\EventMissionAck;
use App\Models\EventNotification;
use App\Models\EventShare;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\MobileDevice;
use App\Models\ModelChange;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperDonation;
use App\Models\TrooperFriend;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Trooper
 * 
 * @property int $id
 * @property string $display_name
 * @property string $legal_name
 * @property string|null $phone
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $setup_completed_at
 * @property string $password
 * @property string $theme
 * @property string $membership_status
 * @property string $membership_role
 * @property string $notification_frequency
 * @property bool $push_notifications_enabled
 * @property Carbon|null $visitor_expires_at
 * @property Carbon|null $visitor_notified_at
 * @property Carbon|null $achievements_updated_at
 * @property Carbon|null $last_active_at
 * @property int|null $guardian_id
 * @property Carbon|null $date_of_birth
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property \App\Models\Trooper|null $trooper
 * @property Collection|Award[] $awards
 * @property Collection|EventGuest[] $event_guests
 * @property Collection|EventMissionAck[] $event_mission_acks
 * @property Collection|EventNotification[] $event_notifications
 * @property Collection|EventShare[] $event_shares
 * @property Collection|EventTrooper[] $event_troopers
 * @property Collection|EventUpload[] $event_uploads
 * @property Collection|MobileDevice[] $mobile_devices
 * @property Collection|ModelChange[] $model_changes
 * @property Collection|Notice[] $notices
 * @property Collection|OauthLogin[] $oauth_logins
 * @property Collection|TrooperAchievement[] $trooper_achievements
 * @property Collection|TrooperAssignment[] $trooper_assignments
 * @property Collection|TrooperCostume[] $trooper_costumes
 * @property Collection|TrooperDonation[] $trooper_donations
 * @property Collection|TrooperFriend[] $trooper_friends
 * @property Collection|Organization[] $organizations
 * @property Collection|\App\Models\Trooper[] $troopers
 *
 * @package App\Models\Base
 */
class Trooper extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const DISPLAY_NAME = 'display_name';
    const LEGAL_NAME = 'legal_name';
    const PHONE = 'phone';
    const EMAIL = 'email';
    const EMAIL_VERIFIED_AT = 'email_verified_at';
    const SETUP_COMPLETED_AT = 'setup_completed_at';
    const PASSWORD = 'password';
    const THEME = 'theme';
    const MEMBERSHIP_STATUS = 'membership_status';
    const MEMBERSHIP_ROLE = 'membership_role';
    const NOTIFICATION_FREQUENCY = 'notification_frequency';
    const PUSH_NOTIFICATIONS_ENABLED = 'push_notifications_enabled';
    const NOTIFICATION_PREFERENCES = 'notification_preferences';
    const VISITOR_EXPIRES_AT = 'visitor_expires_at';
    const VISITOR_NOTIFIED_AT = 'visitor_notified_at';
    const ACHIEVEMENTS_UPDATED_AT = 'achievements_updated_at';
    const LAST_ACTIVE_AT = 'last_active_at';
    const GUARDIAN_ID = 'guardian_id';
    const DATE_OF_BIRTH = 'date_of_birth';
    const REMEMBER_TOKEN = 'remember_token';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    protected $table = 'tt_troopers';

    protected $casts = [
        self::ID => 'int',
        self::EMAIL_VERIFIED_AT => 'datetime',
        self::SETUP_COMPLETED_AT => 'datetime',
        self::PUSH_NOTIFICATIONS_ENABLED => 'bool',
        self::NOTIFICATION_PREFERENCES => 'array',
        self::VISITOR_EXPIRES_AT => 'datetime',
        self::VISITOR_NOTIFIED_AT => 'datetime',
        self::ACHIEVEMENTS_UPDATED_AT => 'datetime',
        self::LAST_ACTIVE_AT => 'datetime',
        self::GUARDIAN_ID => 'int',
        self::DATE_OF_BIRTH => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime'
    ];

    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN
    ];

    protected $fillable = [
        self::DISPLAY_NAME,
        self::LEGAL_NAME,
        self::PHONE,
        self::EMAIL,
        self::EMAIL_VERIFIED_AT,
        self::SETUP_COMPLETED_AT,
        self::PASSWORD,
        self::THEME,
        self::MEMBERSHIP_STATUS,
        self::MEMBERSHIP_ROLE,
        self::NOTIFICATION_FREQUENCY,
        self::PUSH_NOTIFICATIONS_ENABLED,
        self::NOTIFICATION_PREFERENCES,
        self::VISITOR_EXPIRES_AT,
        self::VISITOR_NOTIFIED_AT,
        self::ACHIEVEMENTS_UPDATED_AT,
        self::LAST_ACTIVE_AT,
        self::GUARDIAN_ID,
        self::DATE_OF_BIRTH,
        self::REMEMBER_TOKEN
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Trooper::class, \App\Models\Trooper::GUARDIAN_ID);
    }

    public function awards(): BelongsToMany
    {
        return $this->belongsToMany(Award::class, 'tt_award_troopers')
                    ->withPivot(AwardTrooper::ID, AwardTrooper::AWARD_DATE, AwardTrooper::DELETED_AT, AwardTrooper::CREATED_ID, AwardTrooper::UPDATED_ID, AwardTrooper::DELETED_ID)
                    ->withTimestamps();
    }

    public function event_guests(): HasMany
    {
        return $this->hasMany(EventGuest::class, EventGuest::ADDED_BY_TROOPER_ID);
    }

    public function event_mission_acks(): HasMany
    {
        return $this->hasMany(EventMissionAck::class);
    }

    public function event_notifications(): HasMany
    {
        return $this->hasMany(EventNotification::class);
    }

    public function event_shares(): HasMany
    {
        return $this->hasMany(EventShare::class);
    }

    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }

    public function event_uploads(): HasMany
    {
        return $this->hasMany(EventUpload::class);
    }

    public function mobile_devices(): HasMany
    {
        return $this->hasMany(MobileDevice::class);
    }

    public function model_changes(): HasMany
    {
        return $this->hasMany(ModelChange::class);
    }

    public function notices(): BelongsToMany
    {
        return $this->belongsToMany(Notice::class, 'tt_notice_troopers')
                    ->withPivot(NoticeTrooper::ID, NoticeTrooper::IS_READ, NoticeTrooper::DELETED_AT, NoticeTrooper::CREATED_ID, NoticeTrooper::UPDATED_ID, NoticeTrooper::DELETED_ID)
                    ->withTimestamps();
    }

    public function oauth_logins(): HasMany
    {
        return $this->hasMany(OauthLogin::class);
    }

    public function trooper_achievements(): HasMany
    {
        return $this->hasMany(TrooperAchievement::class);
    }

    public function trooper_assignments(): HasMany
    {
        return $this->hasMany(TrooperAssignment::class);
    }

    public function trooper_costumes(): HasMany
    {
        return $this->hasMany(TrooperCostume::class);
    }

    public function trooper_donations(): HasMany
    {
        return $this->hasMany(TrooperDonation::class);
    }

    public function trooper_friends(): HasMany
    {
        return $this->hasMany(TrooperFriend::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'tt_trooper_organizations')
                    ->withPivot(TrooperOrganization::ID, TrooperOrganization::IDENTIFIER, TrooperOrganization::MEMBERSHIP_STATUS, TrooperOrganization::JOIN_DATE, TrooperOrganization::SYNCHRONIZED_AT, TrooperOrganization::DELETED_AT, TrooperOrganization::CREATED_ID, TrooperOrganization::UPDATED_ID, TrooperOrganization::DELETED_ID)
                    ->withTimestamps();
    }

    public function troopers(): HasMany
    {
        return $this->hasMany(\App\Models\Trooper::class, \App\Models\Trooper::GUARDIAN_ID);
    }
}
