<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventUpload;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * @method static EventUpload call(EventUpload $event_upload)
 */
final class ToggleEventUploadType extends Message
{
    use ShouldBeTransactional;

    public function __construct(private readonly EventUpload $event_upload) {}

    public function handle(): EventUpload
    {
        $upload = $this->event_upload;

        $upload->is_administrative = ! (bool) $upload->is_administrative;
        $upload->save();

        return $upload;
    }
}
