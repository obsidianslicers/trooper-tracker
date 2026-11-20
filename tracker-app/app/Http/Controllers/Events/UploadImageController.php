<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Events\Concerns\HandlesEventImageUploadResponses;
use App\Http\Controllers\PageController;
use App\Models\Event;
use App\Services\EventImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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
     * @return Response Response with updated upload display view and flash message header
     */
    public function __invoke(Request $request, Event $event)
    {
        $trooper_id = $request->user()->id;

        try
        {
            $request->validate($this->eventImageUploadRules(), $this->eventImageUploadMessages());
        }
        catch (ValidationException $e)
        {
            return $this->response($event, $this->eventImageUploadValidationMessage($e), 'danger', HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->uploads->upload(
            $event,
            $trooper_id,
            $request->file('images', []),
            $this->isInstructional($request, $event)
        );

        return $this->response(
            $event,
            $this->eventImageUploadSuccessMessage($result),
            $result->hasFailures() ? 'warning' : 'success',
            $result->hasUploads() ? HttpResponse::HTTP_OK : HttpResponse::HTTP_UNPROCESSABLE_ENTITY
        );
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

    private function response(Event $event, string $message, string $type, int $status = HttpResponse::HTTP_OK)
    {
        $event->load(['event_uploads.troopers', 'event_uploads.trooper']);

        $flash = json_encode($this->eventImageUploadFlash($message, $type));

        return response()
            ->view('pages.events.inc.uploads-panel', compact('event'))
            ->setStatusCode($status)
            ->header('X-Flash-Message', $flash);
    }
}
