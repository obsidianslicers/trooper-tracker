<?php

namespace App\Models;

use App\Models\Base\EventRequest as BaseEventRequest;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventRequest extends BaseEventRequest
{
    use HasFactory;
    use HasTrooperStamps;
}
