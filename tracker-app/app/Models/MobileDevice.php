<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\MobileDevice as BaseMobileDevice;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Stores FCM push notification tokens for mobile devices.
 *
 * Each record links a device's FCM token to a trooper, enabling
 * targeted push notifications. A trooper may have multiple devices.
 */
class MobileDevice extends BaseMobileDevice
{
    use HasFactory;
}
