<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PluginChange
 *
 * @property int $id
 * @property string $slug
 * @property string $action
 * @property string|null $from_version
 * @property string|null $to_version
 * @property array|null $migrations
 * @property string|null $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 */
class PluginChange extends Model
{
    const ID = 'id';

    const SLUG = 'slug';

    const ACTION = 'action';

    const FROM_VERSION = 'from_version';

    const TO_VERSION = 'to_version';

    const MIGRATIONS = 'migrations';

    const MESSAGE = 'message';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    const CREATED_ID = 'created_id';

    const UPDATED_ID = 'updated_id';

    const DELETED_ID = 'deleted_id';

    protected $table = 'tt_plugin_changes';

    protected $casts = [
        self::ID => 'int',
        self::MIGRATIONS => 'json',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int',
    ];

    protected $fillable = [
        self::SLUG,
        self::ACTION,
        self::FROM_VERSION,
        self::TO_VERSION,
        self::MIGRATIONS,
        self::MESSAGE,
    ];
}
