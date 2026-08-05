<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Mail\Admin\Troopers\TroopersMerged;
use App\Models\Trooper;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Notifications\BaseNotification;

class TroopersMergedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'troopers_merged';

    public function __construct(
        private readonly Trooper $source_trooper,
        private readonly Trooper $target_trooper,
    ) {
    }

    public function toMail(Trooper $notifiable): TroopersMerged
    {
        return (new TroopersMerged($this->source_trooper, $this->target_trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => 'Troopers Merged',
            'body' => "{$this->source_trooper->legal_name} (#{$this->source_trooper->id}) was merged into {$this->target_trooper->legal_name} (#{$this->target_trooper->id}).",
            'url' => "/admin/troopers/{$this->target_trooper->id}",
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
