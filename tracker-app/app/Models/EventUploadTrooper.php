<?php

namespace App\Models;

use App\Models\Base\EventUploadTrooper as BaseEventUploadTrooper;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventUploadTrooper extends BaseEventUploadTrooper
{
    use HasFactory;
    use HasTrooperStamps;
}
