<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A photo on the event page, with its share links, Tag me, and moving it between the
 * instructional uploads and the mission review.
 *
 * @mixin EventUpload
 */
class EventDisplayUpload extends JsonResource
{
    public function __construct(EventUpload $event_upload, private readonly Trooper $viewer)
    {
        parent::__construct($event_upload);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event;

        return [
            'id' => $this->id,
            'small_url' => $this->small_url,
            'large_url' => $this->large_url,
            'uploaded_by' => $this->is_administrative ? $this->trooper?->display_name : null,
            'is_tagged' => $this->troopers->contains('id', $this->viewer->id),
            'tag_url' => !$this->is_administrative
                ? route('events.toggle-upload-tag', ['event_upload' => $this->resource]) : null,
            'move_url' => $this->viewer->can('changeType', $this->resource)
                ? route('events.toggle-upload-type', ['event_upload' => $this->resource]) : null,
            'shares' => EventDisplayShareLinks::for(
                route('shares.event-upload', ['event' => $event, 'event_upload' => $this->resource]),
                $event->name,
            ),
        ];
    }
}
