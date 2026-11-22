<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Concerns;

use App\Enums\FlashType;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

/**
 * The event page's own changes (sign-ups, guests, photos, the watch toggle) go back to the
 * page with a toast; the Svelte page reloads only the parts that changed.
 */
trait AnswersWithEventPage
{
    private function backToEvent(
        Event $event,
        ?string $message = null,
        FlashType $type = FlashType::SUCCESS,
    ): RedirectResponse {
        if ($message !== null && $message !== '')
        {
            $type->toast($message);
        }

        return redirect()->route('events.display', ['event' => $event]);
    }
}
