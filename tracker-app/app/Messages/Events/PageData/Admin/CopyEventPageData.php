<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Messages\Events\Resources\Admin\CopyEventResource;
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
            'event' => new CopyEventResource($this->event),
        ];
    }
}
