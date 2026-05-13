<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Costume
 * 
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Collection|EventTrooper[] $event_troopers
 * @property Collection|Organization[] $organizations
 *
 * @package App\Models\Base
 */
class Costume extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const NAME = 'name';
    const SEQUENCE = 'sequence';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_costumes';

    protected $casts = [
        self::ID => 'int',
        self::SEQUENCE => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::NAME,
        self::SEQUENCE,
    ];

    public function event_troopers(): HasMany
    {
        return $this->hasMany(EventTrooper::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'tt_organization_costumes')
                    ->withPivot(OrganizationCostume::ID, OrganizationCostume::PREFIX, OrganizationCostume::SYNCHRONIZED_AT, OrganizationCostume::DELETED_AT, OrganizationCostume::CREATED_ID, OrganizationCostume::UPDATED_ID, OrganizationCostume::DELETED_ID)
                    ->withTimestamps();
    }
}
