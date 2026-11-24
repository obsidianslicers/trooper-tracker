<?php

declare(strict_types=1);

namespace App\Messages\Account\Resources;

use App\Models\TrooperNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the notifications inbox (account/PushNotifications).
 *
 * @mixin TrooperNotification
 */
class PushNotificationResource extends JsonResource
{
    /**
     * @return array{id: string, title: string, body: string, is_unread: bool, sent_ago: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'is_unread' => $this->isUnread(),
            'sent_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
