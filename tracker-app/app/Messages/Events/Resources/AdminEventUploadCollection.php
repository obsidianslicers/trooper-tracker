<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Models\EventUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Event photos for the Administrative Uploads and Mission Review tabs.
 */
class AdminEventUploadCollection extends ResourceCollection
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (EventUpload $upload): array => [
                'id' => $upload->id,
                'small_url' => $upload->small_url,
                'uploaded_by' => $upload->trooper?->display_name,
                'tagged' => $upload->troopers->pluck('display_name')->values()->all(),
            ])
            ->values()
            ->all();
    }
}
