<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Plugin
 *
 * @property int $id
 * @property string $slug
 * @property bool $is_enabled
 * @property bool $is_enable_pending
 * @property string|null $installed_version
 * @property string|null $settings
 * @property Carbon|null $enabled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 */
class Plugin extends Model
{
    const ID = 'id';

    const SLUG = 'slug';

    const IS_ENABLED = 'is_enabled';

    const IS_ENABLE_PENDING = 'is_enable_pending';

    const INSTALLED_VERSION = 'installed_version';

    const SETTINGS = 'settings';

    const ENABLED_AT = 'enabled_at';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    const CREATED_ID = 'created_id';

    const UPDATED_ID = 'updated_id';

    const DELETED_ID = 'deleted_id';

    protected $table = 'tt_plugins';

    protected $casts = [
        self::ID => 'int',
        self::IS_ENABLED => 'bool',
        self::IS_ENABLE_PENDING => 'bool',
        self::ENABLED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int',
    ];

    protected $fillable = [
        self::SLUG,
        self::IS_ENABLED,
        self::IS_ENABLE_PENDING,
        self::INSTALLED_VERSION,
        self::SETTINGS,
        self::ENABLED_AT,
    ];
}
