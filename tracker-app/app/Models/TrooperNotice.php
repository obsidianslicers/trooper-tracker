<?php

namespace App\Models;

use App\Models\Base\TrooperNotice as BaseTrooperNotice;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrooperNotice extends BaseTrooperNotice
{
    use HasFactory;
    use HasTrooperStamps;
}
