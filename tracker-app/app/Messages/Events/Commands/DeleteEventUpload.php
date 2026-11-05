<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Storage;

/**
 * @method static void call(EventUpload $event_upload)
 */
final class DeleteEventUpload extends Message
{
    use ShouldBeTransactional;

    public function __construct(private readonly EventUpload $event_upload) {}

    public function handle(): void
    {
        $upload = $this->event_upload;

        foreach ([$upload->image_path_lg, $upload->image_path_sm] as $path)
        {
            if ($path && str_contains($path, '/'))
            {
                Storage::disk('public')->delete($path);
            }
        }

        EventUploadTrooper::where(EventUploadTrooper::EVENT_UPLOAD_ID, $upload->id)->forceDelete();

        $upload->forceDelete();
    }
}
