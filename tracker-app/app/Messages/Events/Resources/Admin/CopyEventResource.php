<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Admin;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Copy Event form, filled from the event being copied.
 *
 * @mixin Event
 */
class CopyEventResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, event_start: string|null, organization_name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            Event::ID => $this->id,
            Event::NAME => __('admin.COPY OF :name', ['name' => $this->name]),
            Event::EVENT_START => $this->event_start?->format('Y-m-d H:i'),
            'organization_name' => $this->organization->name ?? __('admin.Everyone'),
        ];
    }
}
