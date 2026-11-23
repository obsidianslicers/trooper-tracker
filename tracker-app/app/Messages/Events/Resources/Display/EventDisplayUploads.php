<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Models\Event;
use App\Models\EventUpload;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The event page's photos, split the way the page shows them: the instructional uploads and the
 * mission review, and where new photos go.
 *
 * @mixin Event
 */
class EventDisplayUploads extends JsonResource
{
    public function __construct(Event $event, private readonly Trooper $viewer)
    {
        parent::__construct($event);
    }

    /**
     * @return array{instructional: list<array<string, mixed>>, mission_review: list<array<string, mixed>>, upload_url: string}
     */
    public function toArray(Request $request): array
    {
        [$instructional, $mission_review] = $this->event_uploads()
            ->with(['troopers:id', 'trooper:id,display_name'])
            ->orderBy(EventUpload::ID)
            ->get()
            ->each(fn (EventUpload $upload) => $upload->setRelation('event', $this->resource))
            ->partition(fn (EventUpload $upload) => (bool) $upload->is_administrative);

        return [
            'instructional' => $this->rows($instructional),
            'mission_review' => $this->rows($mission_review),
            'upload_url' => route('events.upload-image', ['event' => $this->resource]),
        ];
    }

    /**
     * @param  iterable<EventUpload>  $uploads
     * @return list<array<string, mixed>>
     */
    private function rows(iterable $uploads): array
    {
        return collect($uploads)
            ->map(fn (EventUpload $upload) => (new EventDisplayUpload($upload, $this->viewer))->resolve())
            ->values()
            ->all();
    }
}
