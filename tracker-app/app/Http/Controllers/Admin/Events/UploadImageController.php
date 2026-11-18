<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\FlashType;
use App\Http\Controllers\Events\Concerns\HandlesEventImageUploadResponses;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
use App\Services\EventImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response as InertiaResponse;

/**
 * Handles administrative event image uploads.
 *
 * This controller processes image uploads for events from administrators/moderators,
 * creating both original and thumbnail versions. Images are marked as administrative
 * uploads.
 */
class UploadImageController extends EventCommandController
{
    use HandlesEventImageUploadResponses;

    public function __construct(private readonly EventImageUploadService $uploads) {}

    /**
     * Handle the incoming administrative image upload request
     *
     * Validates uploaded images (max tracker.image.max_upload_kb, PNG/JPG/JPEG/WEBP/HEIC), normalizes them
     * to square transparent canvases, generates thumbnails (128x128), and stores
     * both versions. Creates EventUpload records marked as administrative uploads.
     *
     * @param  Request  $request  The incoming HTTP request with 'images' file array
     * @param  Event  $event  The event to attach uploads to
     * @return InertiaResponse The refreshed event page data as results
     */
    public function __invoke(Request $request, Event $event): InertiaResponse
    {
        $this->authorize('update', $event);

        try
        {
            $request->validate($this->eventImageUploadRules(), $this->eventImageUploadMessages());
        }
        catch (ValidationException $e)
        {
            FlashType::danger($this->eventImageUploadValidationMessage($e));

            return $this->respondWithResults(
                $request,
                AdminEventPageData::UPLOADS,
                AdminEventPageData::MISSION_REVIEW,
            );
        }

        $result = $this->uploads->upload($event, $request->user()->id, $request->file('images', []), true);
        $message = $this->eventImageUploadSuccessMessage($result);

        match (true)
        {
            !$result->hasUploads() => FlashType::danger($message),
            $result->hasFailures() => FlashType::warning($message),
            default => FlashType::success($message),
        };

        return $this->respondWithResults(
            $request,
            AdminEventPageData::UPLOADS,
            AdminEventPageData::MISSION_REVIEW,
        );
    }
}
