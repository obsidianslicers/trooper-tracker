<?php

namespace App\Models;

use App\Models\Base\EventUploadTrooper as BaseEventUploadTrooper;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents the relationship between an event upload and a trooper.
 *
 * This model tracks which troopers appear in or are tagged in event photos
 * and other uploaded media, enabling photo galleries and trooper-specific
 * event history.
 */
class EventUploadTrooper extends BaseEventUploadTrooper
{
    use HasFactory;
    use HasTrooperStamps;
}
