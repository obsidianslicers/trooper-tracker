<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\Events\Concerns\HandlesEventImageUploadResponses;
use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\UploadEventImages;
use App\Models\Event;
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
class UploadEventImagesController extends PageController
{
    use AnswersWithEventPage;
    use HandlesEventImageUploadResponses;

    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        try
        {
            $request->validate($this->eventImageUploadRules(), $this->eventImageUploadMessages());
        }
        catch (ValidationException $e)
        {
            return $this->backToEvent($event, $this->eventImageUploadValidationMessage($e), FlashType::DANGER);
        }

        $result = UploadEventImages::call(
            event: $event,
            trooper: $request->user(),
            files: $request->file('images', []),
            is_instructional: match ($request->input('upload_type'))
            {
                'instructional' => true,
                'troop' => false,
                default => null,
            },
        );

        $type = match (true)
        {
            !$result->hasUploads() => FlashType::DANGER,
            $result->hasFailures() => FlashType::WARNING,
            default => FlashType::SUCCESS,
        };

        return $this->backToEvent($event, $this->eventImageUploadSuccessMessage($result), $type);
    }
}
