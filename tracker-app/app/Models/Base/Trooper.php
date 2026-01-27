<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

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
 * @property string $name
 * @property string|null $phone
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $setup_completed_at
 * @property string $password
 * @property string $theme
 * @property Carbon|null $last_active_at
 * @property string $membership_status
 * @property string $membership_role
 * @property string $notification_frequency
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
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
    const NAME = 'name';
    const PHONE = 'phone';
    const EMAIL = 'email';
    const EMAIL_VERIFIED_AT = 'email_verified_at';
    const SETUP_COMPLETED_AT = 'setup_completed_at';
    const PASSWORD = 'password';
    const THEME = 'theme';
    const LAST_ACTIVE_AT = 'last_active_at';
    const MEMBERSHIP_STATUS = 'membership_status';
    const MEMBERSHIP_ROLE = 'membership_role';
    const NOTIFICATION_FREQUENCY = 'notification_frequency';
    const REMEMBER_TOKEN = 'remember_token';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    protected $table = 'tt_troopers';

    protected $casts = [
        self::ID => 'int',
        self::EMAIL_VERIFIED_AT => 'datetime',
        self::SETUP_COMPLETED_AT => 'datetime',
        self::LAST_ACTIVE_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime'
    ];

    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN
    ];

    protected $fillable = [
        self::NAME,
        self::PHONE,
        self::EMAIL,
        self::EMAIL_VERIFIED_AT,
        self::SETUP_COMPLETED_AT,
        self::PASSWORD,
        self::THEME,
        self::LAST_ACTIVE_AT,
        self::MEMBERSHIP_STATUS,
        self::MEMBERSHIP_ROLE,
        self::NOTIFICATION_FREQUENCY,
        self::REMEMBER_TOKEN
    ];

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
                    ->withPivot(TrooperOrganization::ID, TrooperOrganization::IDENTIFIER, TrooperOrganization::MEMBERSHIP_STATUS, TrooperOrganization::VERIFIED_AT, TrooperOrganization::DELETED_AT, TrooperOrganization::CREATED_ID, TrooperOrganization::UPDATED_ID, TrooperOrganization::DELETED_ID)
                    ->withTimestamps();
    }
}
