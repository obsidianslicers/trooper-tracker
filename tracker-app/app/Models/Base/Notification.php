<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Notification
 * 
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property string $data
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models\Base
 */
class Notification extends Model
{
    const ID = 'id';
    const TYPE = 'type';
    const NOTIFIABLE_TYPE = 'notifiable_type';
    const NOTIFIABLE_ID = 'notifiable_id';
    const DATA = 'data';
    const READ_AT = 'read_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected $table = 'tt_notifications';
    public $incrementing = false;

    protected $casts = [
        self::NOTIFIABLE_ID => 'int',
        self::READ_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime'
    ];

    protected $fillable = [
        self::TYPE,
        self::NOTIFIABLE_TYPE,
        self::NOTIFIABLE_ID,
        self::DATA,
        self::READ_AT
    ];
}
