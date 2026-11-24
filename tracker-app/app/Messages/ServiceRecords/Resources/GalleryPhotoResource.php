<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One photo in the photo gallery (service-records/PhotoGallery).
 *
 * @mixin EventUpload
 */
class GalleryPhotoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource->event;

        return [
            EventUpload::ID => $this->resource->id,
            'small_url' => $this->resource->small_url,
            'large_url' => $this->resource->large_url,
            'event_name' => $event->name,
            'event_url' => route('events.display', ['event' => $event]),
            'event_date' => $event->event_start?->localFormat('M j, Y'),
            'uploaded_by' => $this->resource->trooper->display_name,
            'tagged' => $this->resource->troopers->pluck(Trooper::DISPLAY_NAME)->join(', '),
        ];
    }
}
