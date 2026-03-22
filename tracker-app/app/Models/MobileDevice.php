<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores FCM push notification tokens for mobile devices.
 *
 * @property int $id
 * @property int|null $trooper_id
 * @property string $fcm_token
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
