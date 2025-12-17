<?php

namespace App\Models;

use App\Models\Base\NoticeTrooper as BaseNoticeTrooper;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents the delivery and read status of a notice to a specific trooper.
 *
 * This model tracks which notices have been sent to which troopers and whether
 * they have been read, enabling notification management and tracking.
 */
class NoticeTrooper extends BaseNoticeTrooper
{
    use HasFactory;
    use HasTrooperStamps;
}
