<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Events\BroadcastMessageHtmxRequest;
use App\Jobs\BroadcastEventMessageJob;
use App\Models\Event;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handles HTMX requests for broadcasting an event message.
 *
 * Validates the recipient email, determines whether the current trooper can
 * moderate the event, and returns the share-roster partial with success/error
 * feedback and validation errors for inline rendering.
 */
class BroadcastMessageHtmxController extends MagicBusController
{
    /**
     * Handle the incoming HTMX request to broadcast an event message.
     *
     * Computes a `can_moderate` flag for view rendering and validates the
     * recipient email. On successful validation it prepares the broadcast command;
     * on validation failure it returns an error message and validator errors.
     *
     * View data includes:
     * - `event`: the current event
     * - `message`: success or failure message
     * - `can_moderate`: whether the current trooper can update the event
     * - `errors`: validation errors (ViewErrorBag default bag)
     *
     * @param  BroadcastMessageHtmxRequest  $request  The validated HTMX request
     * @param  Event  $event  The event being shared
     * @return View The share-roster HTMX partial response
     */
    public function __invoke(BroadcastMessageHtmxRequest $request, Event $event): View
    {
        $can_moderate = $request->user()->can('update', $event);
        $message = null;

        try
        {
            $message = $request->validated('message');

            dispatch(new BroadcastEventMessageJob($event, $message));
        }
        catch (ValidationException $exception)
        {
            $errors = new ViewErrorBag;
            $errors->put('default', $exception->validator->errors());
            ViewFacade::share('errors', $errors);
        }

        $data = compact('event', 'message', 'can_moderate');

        return view('pages.events.inc.broadcast-message', $data);
    }
}
