<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The event page's tools: the moderators' roster download, share and broadcast (only while the
 * event is active, for the latter two), and the watch toggle everyone has.
 *
 * @mixin Event
 */
class EventDisplayTools extends JsonResource
{
    public function __construct(Event $event, private readonly bool $can_moderate)
    {
        parent::__construct($event);
    }

    /**
     * @return array{roster_csv_url: string|null, share_roster_url: string|null, broadcast_url: string|null, broadcast_status: string, watch_url: string}
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource;
        $is_active_moderator = $this->can_moderate && $this->is_active;

        return [
            'roster_csv_url' => $this->can_moderate
                ? route('events.download-roster-csv', ['event' => $event]) : null,
            'share_roster_url' => $is_active_moderator
                ? route('events.share-roster', ['event' => $event]) : null,
            'broadcast_url' => $is_active_moderator
                ? route('events.broadcast-message', ['event' => $event]) : null,
            'broadcast_status' => EventTrooperStatus::GOING->label(),
            'watch_url' => route('events.toggle-watch', ['event' => $event]),
        ];
    }
}
