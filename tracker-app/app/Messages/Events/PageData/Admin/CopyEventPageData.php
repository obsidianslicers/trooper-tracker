<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Models\Event;
use Hyperdrive\Message;

/**
 * The Copy Event form, filled from the event being copied.
 *
 * @method static array call(Event $event)
 */
final class CopyEventPageData extends Message
{
    public function __construct(private readonly Event $event) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'event' => [
                'id' => $this->event->id,
                'name' => __('admin.COPY OF :name', ['name' => $this->event->name]),
                'event_start' => $this->event->event_start?->format('Y-m-d H:i'),
                'organization_name' => $this->event->organization->name ?? __('admin.Everyone'),
            ],
        ];
    }
}
