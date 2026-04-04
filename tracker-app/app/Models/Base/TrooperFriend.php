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
 * Class TrooperFriend
 * 
 * @property int $id
 * @property int $trooper_id
 * @property int $friend_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * 
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class TrooperFriend extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const FRIEND_ID = 'friend_id';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    protected $table = 'tt_trooper_friends';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::FRIEND_ID => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::FRIEND_ID
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
