<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\EventUpload;

/**
 * @implements CommandHandlerInterface<ToggleEventUploadTypeCommand>
 */
readonly class ToggleEventUploadTypeCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  ToggleEventUploadTypeCommand  $message
     */
    public function __invoke(object $message): EventUpload
    {
        $upload = $message->event_upload;

        $upload->is_administrative = ! (bool) $upload->is_administrative;
        $upload->save();

        return $upload;
    }
}
