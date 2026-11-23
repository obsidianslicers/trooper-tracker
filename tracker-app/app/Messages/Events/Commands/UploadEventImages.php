<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\Event;
use App\Models\Trooper;
use App\Services\EventImageUploadResult;
use App\Services\EventImageUploadService;
use Hyperdrive\Message;
use Illuminate\Http\UploadedFile;

/**
 * Stores photos a trooper uploaded to an event, as troop photos or instructional uploads.
 *
 * @method static EventImageUploadResult call(Event $event, Trooper $trooper, array $files, ?bool $is_instructional = null)
 */
final class UploadEventImages extends Message
{
    /**
     * @param  array<int, UploadedFile>  $files
     * @param  bool|null  $is_instructional  Instructional uploads (true) or troop photos (false); null uses
     *                                       the event's default.
     */
    public function __construct(
        private readonly Event $event,
        private readonly Trooper $trooper,
        private readonly array $files,
        private readonly ?bool $is_instructional = null,
    ) {}

    public function handle(EventImageUploadService $uploads): EventImageUploadResult
    {
        return $uploads->upload(
            $this->event,
            $this->trooper->id,
            $this->files,
            $this->is_instructional ?? $this->event->uploadsDefaultToInstructional(),
        );
    }
}
