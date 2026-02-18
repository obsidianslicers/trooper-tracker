<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\EventNotification;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\ModelChange;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperDonation;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
 * @property Carbon|null $achievements_updated_at
 * @property Carbon|null $last_active_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Collection|Award[] $awards
 * @property Collection|EventNotification[] $event_notifications
 * @property Collection|EventTrooper[] $event_troopers
 * @property Collection|EventUpload[] $event_uploads
 * @property Collection|ModelChange[] $model_changes
 * @property Collection|Notice[] $notices
 * @property Collection|OauthLogin[] $oauth_logins
 * @property Collection|TrooperAchievement[] $trooper_achievements
 * @property Collection|TrooperAssignment[] $trooper_assignments
 * @property Collection|TrooperCostume[] $trooper_costumes
 * @property Collection|TrooperDonation[] $trooper_donations
 * @property Collection|Organization[] $organizations
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
    const ACHIEVEMENTS_UPDATED_AT = 'achievements_updated_at';
    const LAST_ACTIVE_AT = 'last_active_at';
    const REMEMBER_TOKEN = 'remember_token';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    protected $table = 'tt_troopers';

    protected $casts = [
        self::ID => 'int',
        self::EMAIL_VERIFIED_AT => 'datetime',
        self::SETUP_COMPLETED_AT => 'datetime',
        self::ACHIEVEMENTS_UPDATED_AT => 'datetime',
        self::LAST_ACTIVE_AT => 'datetime',
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
        self::ACHIEVEMENTS_UPDATED_AT,
        self::LAST_ACTIVE_AT,
        self::REMEMBER_TOKEN
    ];

    public function awards(): BelongsToMany
    {
        return $this->belongsToMany(Award::class, 'tt_award_troopers')
                    ->withPivot(AwardTrooper::ID, AwardTrooper::AWARD_DATE, AwardTrooper::DELETED_AT, AwardTrooper::CREATED_ID, AwardTrooper::UPDATED_ID, AwardTrooper::DELETED_ID)
                    ->withTimestamps();
    }

    public function event_notifications(): HasMany
    {
        return $this->hasMany(EventNotification::class);
    }

    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }

    public function event_uploads(): HasMany
    {
        return $this->hasMany(EventUpload::class);
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

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'tt_trooper_organizations')
                    ->withPivot(TrooperOrganization::ID, TrooperOrganization::IDENTIFIER, TrooperOrganization::MEMBERSHIP_STATUS, TrooperOrganization::SYNCHRONIZED_AT, TrooperOrganization::DELETED_AT, TrooperOrganization::CREATED_ID, TrooperOrganization::UPDATED_ID, TrooperOrganization::DELETED_ID)
                    ->withTimestamps();
    }
}
