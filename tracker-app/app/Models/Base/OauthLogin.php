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
 * Class OauthLogin
 * 
 * @property int $id
 * @property int $trooper_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $token
 * @property string|null $refresh_token
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class OauthLogin extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const PROVIDER = 'provider';
    const PROVIDER_ID = 'provider_id';
    const TOKEN = 'token';
    const REFRESH_TOKEN = 'refresh_token';
    const EXPIRES_AT = 'expires_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_oauth_logins';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::EXPIRES_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $hidden = [
        self::TOKEN,
        self::REFRESH_TOKEN
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::PROVIDER,
        self::PROVIDER_ID,
        self::TOKEN,
        self::REFRESH_TOKEN,
        self::EXPIRES_AT
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
