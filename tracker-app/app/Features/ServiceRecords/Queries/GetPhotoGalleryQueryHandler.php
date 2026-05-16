<?php

declare(strict_types=1);

namespace App\Features\ServiceRecords\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\EventUpload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for retrieving the community photo gallery.
 *
 * Returns the most recent non-instructional event uploads, paginated,
 * with event and uploader data eager-loaded.
 *
 * @implements QueryHandlerInterface<GetPhotoGalleryQuery>
 */
readonly class GetPhotoGalleryQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  GetPhotoGalleryQuery  $message
     * @return LengthAwarePaginator
     */
    public function __invoke(object $message): LengthAwarePaginator
    {
        return EventUpload::where(EventUpload::IS_ADMINISTRATIVE, false)
            ->with(['event', 'trooper', 'troopers'])
            ->latest()
            ->paginate(24);
    }
}
