<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\EventUploadTrooper;
use Illuminate\Support\Facades\Storage;

/**
 * @implements CommandHandlerInterface<DeleteEventUploadCommand>
 */
readonly class DeleteEventUploadCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  DeleteEventUploadCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $upload = $message->event_upload;

        foreach ([$upload->image_path_lg, $upload->image_path_sm] as $path)
        {
            if ($path && str_contains($path, '/'))
            {
                Storage::disk('public')->delete($path);
            }
        }

        EventUploadTrooper::where(EventUploadTrooper::EVENT_UPLOAD_ID, $upload->id)->forceDelete();

        $upload->forceDelete();

        return null;
    }
}
