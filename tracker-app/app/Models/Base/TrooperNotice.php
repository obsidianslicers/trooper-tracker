<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Notice;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TrooperNotice
 * 
 * @property int $id
 * @property int $trooper_id
 * @property int $notice_id
 * @property bool $is_read
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Notice $notice
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class TrooperNotice extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const NOTICE_ID = 'notice_id';
    const IS_READ = 'is_read';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_trooper_notices';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::NOTICE_ID => 'int',
        self::IS_READ => 'bool',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::NOTICE_ID,
        self::IS_READ
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
