<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Award;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class AwardTrooper
 * 
 * @property int $id
 * @property int $award_id
 * @property int $trooper_id
 * @property Carbon $award_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Award $award
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class AwardTrooper extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const AWARD_ID = 'award_id';
    const TROOPER_ID = 'trooper_id';
    const AWARD_DATE = 'award_date';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_award_troopers';

    protected $casts = [
        self::ID => 'int',
        self::AWARD_ID => 'int',
        self::TROOPER_ID => 'int',
        self::AWARD_DATE => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::AWARD_ID,
        self::TROOPER_ID,
        self::AWARD_DATE
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
