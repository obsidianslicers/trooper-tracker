<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Models\EventUpload;

readonly class ToggleEventUploadTypeCommand
{
    public function __construct(public EventUpload $event_upload) {}
}
