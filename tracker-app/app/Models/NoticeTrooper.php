<?php

namespace App\Models;

use App\Models\Base\NoticeTrooper as BaseNoticeTrooper;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NoticeTrooper extends BaseNoticeTrooper
{
    use HasFactory;
    use HasTrooperStamps;
}
