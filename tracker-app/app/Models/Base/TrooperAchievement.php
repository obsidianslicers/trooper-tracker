<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TrooperAchievement
 * 
 * @property int $id
 * @property int $trooper_id
 * @property string $type
 * @property string|null $value
 * @property Carbon|null $earned_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class TrooperAchievement extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const TYPE = 'type';
    const VALUE = 'value';
    const EARNED_ON = 'earned_on';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    protected $table = 'tt_trooper_achievements';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::EARNED_ON => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::TYPE,
        self::VALUE,
        self::EARNED_ON
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
