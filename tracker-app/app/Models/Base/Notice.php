<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\NoticeTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Notice
 * 
 * @property int $id
 * @property int|null $organization_id
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property string $title
 * @property string $type
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Organization|null $organization
 * @property Collection|Trooper[] $troopers
 *
 * @package App\Models\Base
 */
class Notice extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const ORGANIZATION_ID = 'organization_id';
    const STARTS_AT = 'starts_at';
    const ENDS_AT = 'ends_at';
    const TITLE = 'title';
    const TYPE = 'type';
    const MESSAGE = 'message';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_notices';

    protected $casts = [
        self::ID => 'int',
        self::ORGANIZATION_ID => 'int',
        self::STARTS_AT => 'datetime',
        self::ENDS_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::ORGANIZATION_ID,
        self::STARTS_AT,
        self::ENDS_AT,
        self::TITLE,
        self::TYPE,
        self::MESSAGE
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function troopers(): BelongsToMany
    {
        return $this->belongsToMany(Trooper::class, 'tt_notice_troopers')
                    ->withPivot(NoticeTrooper::ID, NoticeTrooper::IS_READ, NoticeTrooper::DELETED_AT, NoticeTrooper::CREATED_ID, NoticeTrooper::UPDATED_ID, NoticeTrooper::DELETED_ID)
                    ->withTimestamps();
    }
}
