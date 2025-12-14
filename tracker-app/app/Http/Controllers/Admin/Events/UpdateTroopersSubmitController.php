<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\UpdateTroopersRequest;
use App\Models\Event;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class UpdateController
 *
 * Handles displaying the form to update an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class UpdateTroopersSubmitController extends Controller
{
    /**
     * UpdateSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the request to display the event update page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing event.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Event $event The event to be updated.
     * @return RedirectResponse A redirect response to the events list.
     */
    public function __invoke(UpdateTroopersRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $troopers = $request->validated('troopers');

        $event_troopers = $event->troopers()->get();

        foreach ($troopers as $id => $input)
        {
            $event_trooper = $event_troopers->filter(fn($et) => $et->id === (int) $id)->first();

            if ($event_trooper === null)
            {
                continue;
            }

            $event_trooper->status = $input['status'];

            $event_trooper->save();
        }

        $this->flash->updated($event);

        return redirect()->route('admin.events.troopers', compact('event'));
    }
}
