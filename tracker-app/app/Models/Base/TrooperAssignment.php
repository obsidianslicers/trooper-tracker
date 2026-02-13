<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TrooperAssignment
 * 
 * @property int $id
 * @property int $trooper_id
 * @property int $organization_id
 * @property bool $can_notify
 * @property bool $is_member
 * @property bool $is_moderator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Organization $organization
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class TrooperAssignment extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const ORGANIZATION_ID = 'organization_id';
    const CAN_NOTIFY = 'can_notify';
    const IS_MEMBER = 'is_member';
    const IS_MODERATOR = 'is_moderator';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_trooper_assignments';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::ORGANIZATION_ID => 'int',
        self::CAN_NOTIFY => 'bool',
        self::IS_MEMBER => 'bool',
        self::IS_MODERATOR => 'bool',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::ORGANIZATION_ID,
        self::CAN_NOTIFY,
        self::IS_MEMBER,
        self::IS_MODERATOR
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
