<?php

declare(strict_types=1);

namespace App\Notifications\Admin;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\Admin\Troopers\TrooperResubmitted;
use App\Models\Trooper;
use App\Notifications\BaseNotification;

class TrooperResubmittedNotification extends BaseNotification
{
    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = 'trooper_registrations';

    public function __construct(private readonly Trooper $trooper) {}

    public function toMail(Trooper $notifiable): TrooperResubmitted
    {
        return (new TrooperResubmitted($this->trooper))->to($notifiable->email);
    }

    public function toArray(Trooper $notifiable): array
    {
        return [
            'title' => __('notifications.Trooper Resubmitted Application'),
            'body' => __('notifications.:trooper has resubmitted their application after a denial.', ['trooper' => $this->trooper->display_name]),
            'url' => route('admin.troopers.approvals.index', absolute: false),
        ];
    }

    public function toFcm(Trooper $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
