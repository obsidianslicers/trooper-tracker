<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MobileDevice
 *
 * @property int $id
 * @property int|null $trooper_id
 * @property string $fcm_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Trooper|null $trooper
 *
 * @package App\Models\Base
 */
class MobileDevice extends Model
{
    const ID         = 'id';
    const TROOPER_ID = 'trooper_id';
    const FCM_TOKEN  = 'fcm_token';

    protected $table = 'tt_mobile_devices';

    protected $fillable = [
        self::TROOPER_ID,
        self::FCM_TOKEN,
    ];

    public function trooper(): BelongsTo
    {
        return $this->belongsTo(Trooper::class);
    }
}
