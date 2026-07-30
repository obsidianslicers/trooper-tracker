<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\TrooperFriend as BaseTrooperFriend;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrooperFriend extends BaseTrooperFriend
{
    use HasFactory;



    public function friend(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::FRIEND_ID);
    }
}
