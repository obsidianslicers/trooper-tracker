<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Events\Concerns\HandlesEventImageUploadResponses;
use App\Messages\Events\Commands\UploadEventImages;
use App\Messages\Events\PageData\AdminEventPageData;
use App\Models\Event;
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
class StoreEventUploadController extends EventCommandController
{
    use HandlesEventImageUploadResponses;

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

            return $this->respondWithUploads($request);
        }

        $result = UploadEventImages::call(
            event: $event,
            trooper: $request->user(),
            files: $request->file('images', []),
            is_instructional: true,
        );

        $message = $this->eventImageUploadSuccessMessage($result);

        match (true)
        {
            !$result->hasUploads() => FlashType::danger($message),
            $result->hasFailures() => FlashType::warning($message),
            default => FlashType::success($message),
        };

        return $this->respondWithUploads($request);
    }

    private function respondWithUploads(Request $request): InertiaResponse
    {
        return $this->respondWithResults(
            $request,
            AdminEventPageData::UPLOADS,
            AdminEventPageData::MISSION_REVIEW,
        );
    }
}
