<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Queries;

use App\Models\EventUpload;
use Hyperdrive\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Query to retrieve paginated non-instructional event photo uploads.
 *
 * @method static LengthAwarePaginator call()
 */
final class GetPhotoGallery extends Message
{
    public function handle(): LengthAwarePaginator
    {
        return EventUpload::where(EventUpload::IS_ADMINISTRATIVE, false)
            ->with(['event', 'trooper', 'troopers'])
            ->latest()
            ->paginate(24);
    }
}
