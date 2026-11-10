<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class EventType
 * 
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_counted
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property Collection|Event[] $events
 *
 * @package App\Models\Base
 */
class EventType extends Model
{
    const ID = 'id';
    const SLUG = 'slug';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const SORT_ORDER = 'sort_order';
    const IS_COUNTED = 'is_counted';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_event_types';

    protected $casts = [
        self::ID => 'int',
        self::SORT_ORDER => 'int',
        self::IS_COUNTED => 'bool',
        self::IS_ACTIVE => 'bool',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::SLUG,
        self::NAME,
        self::DESCRIPTION,
        self::SORT_ORDER,
        self::IS_COUNTED,
        self::IS_ACTIVE
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
