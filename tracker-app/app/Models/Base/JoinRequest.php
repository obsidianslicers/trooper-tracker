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
 * Class JoinRequest
 * 
 * @property int $id
 * @property int $trooper_id
 * @property int $organization_id
 * @property int $primary_organization_id
 * @property string|null $identifier
 * @property string $status
 * @property int|null $approved_by_id
 * @property Carbon|null $approved_at
 * @property int|null $denied_by_id
 * @property Carbon|null $denied_at
 * @property string|null $denial_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Trooper $trooper
 * @property Organization $organization
 *
 * @package App\Models\Base
 */
class JoinRequest extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const ORGANIZATION_ID = 'organization_id';
    const PRIMARY_ORGANIZATION_ID = 'primary_organization_id';
    const IDENTIFIER = 'identifier';
    const STATUS = 'status';
    const APPROVED_BY_ID = 'approved_by_id';
    const APPROVED_AT = 'approved_at';
    const DENIED_BY_ID = 'denied_by_id';
    const DENIED_AT = 'denied_at';
    const DENIAL_REASON = 'denial_reason';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_join_requests';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::ORGANIZATION_ID => 'int',
        self::PRIMARY_ORGANIZATION_ID => 'int',
        self::APPROVED_BY_ID => 'int',
        self::APPROVED_AT => 'datetime',
        self::DENIED_BY_ID => 'int',
        self::DENIED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::ORGANIZATION_ID,
        self::PRIMARY_ORGANIZATION_ID,
        self::IDENTIFIER,
        self::STATUS,
        self::APPROVED_BY_ID,
        self::APPROVED_AT,
        self::DENIED_BY_ID,
        self::DENIED_AT,
        self::DENIAL_REASON
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, \App\Models\JoinRequest::PRIMARY_ORGANIZATION_ID);
    }
}
