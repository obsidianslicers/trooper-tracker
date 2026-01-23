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
 * Class ModelChange
 * 
 * @property int $id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property int|null $trooper_id
 * @property string $field_name
 * @property string|null $old_value
 * @property string|null $new_value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Trooper|null $trooper
 *
 * @package App\Models\Base
 */
class ModelChange extends Model
{
    const ID = 'id';
    const AUDITABLE_TYPE = 'auditable_type';
    const AUDITABLE_ID = 'auditable_id';
    const TROOPER_ID = 'trooper_id';
    const FIELD_NAME = 'field_name';
    const OLD_VALUE = 'old_value';
    const NEW_VALUE = 'new_value';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_model_changes';

    protected $casts = [
        self::ID => 'int',
        self::AUDITABLE_ID => 'int',
        self::TROOPER_ID => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::AUDITABLE_TYPE,
        self::AUDITABLE_ID,
        self::TROOPER_ID,
        self::FIELD_NAME,
        self::OLD_VALUE,
        self::NEW_VALUE
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
