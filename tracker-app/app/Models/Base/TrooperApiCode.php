<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TrooperApiCode
 * 
 * @property int $id
 * @property int $trooper_id
 * @property string $api_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class TrooperApiCode extends Model
{
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const API_CODE = 'api_code';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected $table = 'tt_trooper_api_codes';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::API_CODE
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
