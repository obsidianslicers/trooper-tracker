<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\FlashType;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\Events\Concerns\HandlesEventImageUploadResponses;
use App\Http\Controllers\PageController;
use App\Models\Event;
use App\Services\EventImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Handles event image uploads from troopers.
 *
 * The uploader picks instructional (maps, directions) or troop photo; without a
 * choice, uploads before the event starts are instructional.
 *
 * This controller processes image uploads for events, creating both original
 * and thumbnail versions. Images are normalized to square canvases with
 * transparency and stored in the public storage disk.
 */
class UploadImageController extends PageController
{
    use AnswersWithEventPage;
    use HandlesEventImageUploadResponses;

    public function __construct(private readonly EventImageUploadService $uploads) {}

    /**
     * Handle the incoming image upload request
     *
     * Validates uploaded images (max tracker.image.max_upload_kb, PNG/JPG/JPEG/WEBP/HEIC), normalizes them
     * to square transparent canvases, generates thumbnails (128x128), and stores
     * both versions. Creates EventUpload records linked to the trooper and event.
     *
     * @param  Request  $request  The incoming HTTP request with 'images' file array
     * @param  Event  $event  The event to attach uploads to
     * @return RedirectResponse Back to the event page, with a toast saying how it went
     */
    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        $trooper_id = $request->user()->id;

        try
        {
            $request->validate($this->eventImageUploadRules(), $this->eventImageUploadMessages());
        }
        catch (ValidationException $e)
        {
            return $this->backToEvent($event, $this->eventImageUploadValidationMessage($e), FlashType::DANGER);
        }

        $result = $this->uploads->upload(
            $event,
            $trooper_id,
            $request->file('images', []),
            $this->isInstructional($request, $event)
        );

        $type = match (true)
        {
            !$result->hasUploads() => FlashType::DANGER,
            $result->hasFailures() => FlashType::WARNING,
            default => FlashType::SUCCESS,
        };

        return $this->backToEvent($event, $this->eventImageUploadSuccessMessage($result), $type);
    }

    private function isInstructional(Request $request, Event $event): bool
    {
        return match ($request->input('upload_type'))
        {
            'instructional' => true,
            'troop' => false,
            default => $event->uploadsDefaultToInstructional(),
        };
    }
}
