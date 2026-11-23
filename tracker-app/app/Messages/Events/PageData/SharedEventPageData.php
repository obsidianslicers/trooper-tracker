<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Models\Event;
use App\Models\EventUpload;
use Hyperdrive\Message;

/**
 * A shared event, or one of its photos, with the image to show when the link is previewed.
 *
 * @method static array<string, mixed> call(Event $event, ?EventUpload $event_upload = null)
 */
final class SharedEventPageData extends Message
{
    public function __construct(
        private readonly Event $event,
        private readonly ?EventUpload $event_upload = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $event_upload = $this->event_upload
            ? $this->event->event_uploads()->where(EventUpload::ID, $this->event_upload->id)->first()
            : null;

        $sharing_image = $event_upload !== null && (bool) $event_upload->large_url;

        return [
            'event' => $this->event,
            'event_upload' => $event_upload,
            'image_url' => $sharing_image
                ? $event_upload->large_url
                : route('shares.og-image', $this->event->organization->id),
            'sharing_image' => $sharing_image,
        ];
    }
}
