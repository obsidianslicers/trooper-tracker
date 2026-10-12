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
 * Class PermissionRequest
 * 
 * @property int $id
 * @property int $trooper_id
 * @property int|null $requester_trooper_id
 * @property int|null $reviewer_trooper_id
 * @property string|null $membership_role
 * @property array|null $moderated_organization_ids
 * @property array|null $forum_groups_to_add
 * @property array|null $forum_groups_to_remove
 * @property string|null $note
 * @property string $status
 * @property string|null $denial_reason
 * @property Carbon|null $reviewed_at
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
class PermissionRequest extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const REQUESTER_TROOPER_ID = 'requester_trooper_id';
    const REVIEWER_TROOPER_ID = 'reviewer_trooper_id';
    const MEMBERSHIP_ROLE = 'membership_role';
    const MODERATED_ORGANIZATION_IDS = 'moderated_organization_ids';
    const FORUM_GROUPS_TO_ADD = 'forum_groups_to_add';
    const FORUM_GROUPS_TO_REMOVE = 'forum_groups_to_remove';
    const NOTE = 'note';
    const STATUS = 'status';
    const DENIAL_REASON = 'denial_reason';
    const REVIEWED_AT = 'reviewed_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_permission_requests';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::REQUESTER_TROOPER_ID => 'int',
        self::REVIEWER_TROOPER_ID => 'int',
        self::MODERATED_ORGANIZATION_IDS => 'json',
        self::FORUM_GROUPS_TO_ADD => 'json',
        self::FORUM_GROUPS_TO_REMOVE => 'json',
        self::REVIEWED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::REQUESTER_TROOPER_ID,
        self::REVIEWER_TROOPER_ID,
        self::MEMBERSHIP_ROLE,
        self::MODERATED_ORGANIZATION_IDS,
        self::FORUM_GROUPS_TO_ADD,
        self::FORUM_GROUPS_TO_REMOVE,
        self::NOTE,
        self::STATUS,
        self::DENIAL_REASON,
        self::REVIEWED_AT
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
