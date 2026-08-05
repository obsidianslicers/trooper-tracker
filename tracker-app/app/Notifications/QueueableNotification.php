<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class QueueableNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;
}
