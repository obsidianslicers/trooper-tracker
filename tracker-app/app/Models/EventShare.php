<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\EventShare as BaseEventShare;

class EventShare extends BaseEventShare
{
    use HasFactory;
    use HasUuids;

    /**
     * Get the route key for the model.
     * This tells Laravel to use 'share_token' for URL binding.
     */
    public function getRouteKeyName(): string
    {
        return 'share_token';
    }
}
