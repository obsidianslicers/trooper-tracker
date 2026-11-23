<?php

declare(strict_types=1);

namespace App\Messages\Api\PageData;

use App\Models\EventUpload;
use Hyperdrive\Message;

/**
 * Random troop photos for the public API, skipping any whose file has gone missing.
 *
 * The keys are the legacy API's and must not change.
 *
 * @method static array call(int $amount)
 */
final class PublicPhotosPageData extends Message
{
    public function __construct(private readonly int $amount) {}

    public function handle(): array
    {
        // Over-fetch so photos whose files have gone missing can be skipped.
        return EventUpload::where(EventUpload::IS_ADMINISTRATIVE, false)
            ->inRandomOrder()
            ->limit($this->amount * 3)
            ->get()
            ->filter(fn (EventUpload $upload): bool => $upload->hasOriginalFile())
            ->take($this->amount)
            ->map(fn (EventUpload $upload) => [
                'fileName' => $upload->image_path_sm,
                'imageUrl' => $upload->display_url,
                'thumbnailUrl' => $upload->small_url,
                'troopID' => $upload->event_id,
                'trooperID' => $upload->trooper_id,
            ])
            ->values()
            ->toArray();
    }
}
