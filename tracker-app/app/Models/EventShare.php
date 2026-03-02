<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\EventShare as BaseEventShare;

/**
 * Model representing a shareable link for an `Event`.
 *
 * An EventShare provides a token-based URL that can be sent to external
 * recipients so they can view event details without authenticating. Shares
 * may be revoked or set to expire at a given date/time.
 */
class EventShare extends BaseEventShare
{
    use HasFactory;

    /**
     * Get the route key for the model.
     *
     * This tells Laravel to use the `share_token` column when performing
     * implicit route model binding for `EventShare` instances.
     *
     * @return string The column name used as the route key.
     */
    public function getRouteKeyName(): string
    {
        return 'share_token';
    }

    /**
     * Determine whether the share is currently viewable.
     *
     * A share is considered viewable when it has not been revoked and its
     * optional `expires_at` timestamp has not passed.
     *
     * @return bool True when the share can be viewed; false otherwise.
     */
    public function getIsViewableAttribute(): bool
    {
        if ($this->is_revoked)
        {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast())
        {
            return false;
        }

        return true;
    }
}
