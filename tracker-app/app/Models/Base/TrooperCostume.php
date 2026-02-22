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
 * @property int $organization_costume_id
 * @property string|null $image_url_sm
 * @property string|null $image_url_lg
 * @property string|null $image_url_bucket_off
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
    const ORGANIZATION_COSTUME_ID = 'organization_costume_id';
    const IMAGE_URL_SM = 'image_url_sm';
    const IMAGE_URL_LG = 'image_url_lg';
    const IMAGE_URL_BUCKET_OFF = 'image_url_bucket_off';
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
        self::ORGANIZATION_COSTUME_ID => 'int',
        self::SYNCHRONIZED_AT => 'datetime',
        self::CREATED_AT => 'datetime',
        self::UPDATED_AT => 'datetime',
        self::CREATED_ID => 'int',
        self::UPDATED_ID => 'int',
        self::DELETED_ID => 'int'
    ];

    protected $fillable = [
        self::TROOPER_ID,
        self::ORGANIZATION_COSTUME_ID,
        self::IMAGE_URL_SM,
        self::IMAGE_URL_LG,
        self::IMAGE_URL_BUCKET_OFF,
        self::SYNCHRONIZED_AT
    ];

    public function organization_costume(): BelongsTo
    {
        return $this->belongsTo(OrganizationCostume::class);
    }

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
