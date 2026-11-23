<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Http\Controllers\Events\Concerns\AnswersWithEventPage;
use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\SignUpEventGuests;
use App\Models\EventShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Adds guests (people without a tracker account) to a shift from the event page.
 *
 * Creates guest records for non-empty names submitted in the
 * newline-delimited guest_names payload.
 */
class SignUpGuestController extends PageController
{
    use AnswersWithEventPage;

    /**
     * Creates new guest entries for the current trooper and goes back to the event page.
     */
    public function __invoke(Request $request, EventShift $event_shift): RedirectResponse
    {
        $guest_names = preg_split('/\\r\\n|\\r|\\n|\\\\n/', (string) $request->input('guest_names')) ?: [];

        $added = SignUpEventGuests::call(
            event_shift: $event_shift,
            added_by_trooper: $request->user(),
            guest_names: $guest_names,
        );

        return $this->backToEvent($event_shift->event, $added ? __('events.Guest added.') : null);
    }
}
