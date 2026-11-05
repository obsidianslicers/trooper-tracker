<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class PluginPackage
 *
 * @property int $id
 * @property string $name
 * @property string|null $version_constraint
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 */
class PluginPackage extends Model
{
    const ID = 'id';

    const NAME = 'name';

    const VERSION_CONSTRAINT = 'version_constraint';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    const CREATED_ID = 'created_id';

    const UPDATED_ID = 'updated_id';

    const DELETED_ID = 'deleted_id';

    protected $table = 'tt_plugin_packages';

    protected $casts = [
        self::ID => 'int',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int',
    ];

    protected $fillable = [
        self::NAME,
        self::VERSION_CONSTRAINT,
    ];
}
