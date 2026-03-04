<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Features\Events\Commands\ShareEventRosterCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\ShareRosterHtmxRequest;
use App\Models\Event;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handles HTMX requests for sharing an event roster.
 *
 * Validates the recipient email, determines whether the current trooper can
 * moderate the event, and returns the share-roster partial with success/error
 * feedback and validation errors for inline rendering.
 */
class ShareRosterHtmxController extends MagicBusController
{
    /**
     * Handle the incoming HTMX request to share an event roster.
     *
     * Computes a `can_moderate` flag for view rendering and validates the
     * recipient email. On successful validation it prepares the share command;
     * on validation failure it returns an error message and validator errors.
     *
     * View data includes:
     * - `event`: the current event
     * - `message`: success or failure message
     * - `can_moderate`: whether the current trooper can update the event
     * - `errors`: validation errors (ViewErrorBag default bag)
     *
     * @param  ShareRosterHtmxRequest  $request  The validated HTMX request
     * @param  Event  $event  The event being shared
     * @return View The share-roster HTMX partial response
     */
    public function __invoke(ShareRosterHtmxRequest $request, Event $event): View
    {
        $can_moderate = $request->user()->can('update', $event);

        $errors = new ViewErrorBag;

        $message = null;

        try
        {
            $request->validateInputs();

            $recipient_email = $request->validated('recipient_email');

            $command = new ShareEventRosterCommand($event, $recipient_email, $request->user());

            $this->bus->send($command);

            $message = 'Roster successfully sent.';
        }
        catch (ValidationException $x)
        {
            $err = $x->errors();

            $display = implode(', ', $err['recipient_email'] ?? ['Unknown error']);

            $message = 'Failed to share roster: '.$display;

            $errors->put('default', $x->validator->errors());
        }

        $data = compact('event', 'message', 'can_moderate', 'errors');

        return view('pages.events.inc.share-roster', $data);
    }
}
