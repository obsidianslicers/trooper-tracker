<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Award
 * 
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $frequency
 * @property bool $has_multiple_recipients
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Organization $organization
 * @property Collection|Trooper[] $troopers
 *
 * @package App\Models\Base
 */
class Award extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const ORGANIZATION_ID = 'organization_id';
    const NAME = 'name';
    const FREQUENCY = 'frequency';
    const HAS_MULTIPLE_RECIPIENTS = 'has_multiple_recipients';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_awards';

    protected $casts = [
        self::ID => 'int',
        self::ORGANIZATION_ID => 'int',
        self::HAS_MULTIPLE_RECIPIENTS => 'bool',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::ORGANIZATION_ID,
        self::NAME,
        self::FREQUENCY,
        self::HAS_MULTIPLE_RECIPIENTS
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function troopers(): BelongsToMany
    {
        return $this->belongsToMany(Trooper::class, 'tt_award_troopers')
                    ->withPivot(AwardTrooper::ID, AwardTrooper::AWARD_DATE, AwardTrooper::DELETED_AT, AwardTrooper::CREATED_ID, AwardTrooper::UPDATED_ID, AwardTrooper::DELETED_ID)
                    ->withTimestamps();
    }
}
