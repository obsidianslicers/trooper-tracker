<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TrooperCostume
 * 
 * @property int $id
 * @property int $trooper_id
 * @property int $costume_id
 * @property string|null $small_image_url
 * @property string|null $large_image_url
 * @property string|null $bucket_off_url
 * @property Carbon|null $synchronized_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $created_id
 * @property int|null $updated_id
 * @property int|null $deleted_id
 * 
 * @property OrganizationCostume $organization_costume
 * @property Trooper $trooper
 *
 * @package App\Models\Base
 */
class TrooperCostume extends Model
{
    use SoftDeletes;
    const ID = 'id';
    const TROOPER_ID = 'trooper_id';
    const COSTUME_ID = 'costume_id';
    const SMALL_IMAGE_URL = 'small_image_url';
    const LARGE_IMAGE_URL = 'large_image_url';
    const BUCKET_OFF_URL = 'bucket_off_url';
    const SYNCHRONIZED_AT = 'synchronized_at';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';
    const CREATED_ID = 'created_id';
    const UPDATED_ID = 'updated_id';
    const DELETED_ID = 'deleted_id';
    protected $table = 'tt_trooper_costumes';

    protected $casts = [
        self::ID => 'int',
        self::TROOPER_ID => 'int',
        self::COSTUME_ID => 'int',
        self::SYNCHRONIZED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::COSTUME_ID,
        self::SMALL_IMAGE_URL,
        self::LARGE_IMAGE_URL,
        self::BUCKET_OFF_URL,
        self::SYNCHRONIZED_AT
    ];

    public function organization_costume(): BelongsTo
    {
        return $this->belongsTo(OrganizationCostume::class, \App\Models\TrooperCostume::COSTUME_ID);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
