<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Events\Concerns\HandlesEventImageUploadResponses;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Services\EventImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\ValidationException;

/**
 * Handles event image uploads from troopers.
 *
 * This controller processes image uploads for events, creating both original
 * and thumbnail versions. Images are normalized to square canvases with
 * transparency and stored in the public storage disk.
 */
class UploadImageController extends MagicBusController
{
    use HandlesEventImageUploadResponses;

    public function __construct(private readonly EventImageUploadService $uploads) {}

    /**
     * Handle the incoming image upload request
     *
     * Validates uploaded images (max 12MB, PNG/JPG/JPEG/WEBP), normalizes them
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

        $result = $this->uploads->upload($event, $trooper_id, $request->file('images', []), false);

        return $this->response(
            $event,
            $this->eventImageUploadSuccessMessage($result),
            $result->hasFailures() ? 'warning' : 'success',
            $result->hasUploads() ? HttpResponse::HTTP_OK : HttpResponse::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    private function response(Event $event, string $message, string $type, int $status = HttpResponse::HTTP_OK)
    {
        $is_administrative = false;
        $event->load(['event_uploads.troopers']);

        $data = compact('event', 'is_administrative');
        $flash = json_encode($this->eventImageUploadFlash($message, $type));

        return response()
            ->view('pages.events.inc.upload-display', $data)
            ->setStatusCode($status)
            ->header('X-Flash-Message', $flash);
    }
}
