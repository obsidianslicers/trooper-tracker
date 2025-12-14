<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\TrooperCostume;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class OrganizationCostume
 * 
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Organization $organization
 * @property Collection|EventTrooper[] $event_troopers
 * @property Collection|TrooperCostume[] $trooper_costumes
 *
 * @package App\Models\Base
 */
class OrganizationCostume extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const ORGANIZATION_ID = 'organization_id';
    const NAME = 'name';
    const VERIFIED_AT = 'verified_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_organization_costumes';

    protected $casts = [
        self::ID => 'int',
        self::ORGANIZATION_ID => 'int',
        self::VERIFIED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::ORGANIZATION_ID,
        self::NAME,
        self::VERIFIED_AT
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class, EventTrooper::COSTUME_ID);
    }

    public function trooper_costumes(): HasMany
    {
        return $this->hasMany(TrooperCostume::class, TrooperCostume::COSTUME_ID);
    }
}
