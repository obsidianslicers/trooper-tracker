<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TrooperApiCode
 * 
 * @property int $id
 * @property int $trooperid
 * @property string $api_code
 * @property Carbon $date_created
 *
 * @package App\Models\Base
 */
class TrooperApiCode extends Model
{
    const ID = 'id';
    const TROOPERID = 'trooperid';
    const API_CODE = 'api_code';
    const DATE_CREATED = 'date_created';
    protected $table = 'trooper_api_codes';
    public $timestamps = false;

    protected $casts = [
        self::ID => 'int',
        self::TROOPERID => 'int',
        self::DATE_CREATED => 'datetime'
    ];

    protected $fillable = [
        self::TROOPERID,
        self::API_CODE,
        self::DATE_CREATED
    ];
}
