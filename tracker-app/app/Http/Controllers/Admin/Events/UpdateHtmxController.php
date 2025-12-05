<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * Class UpdateController
 *
 * Handles displaying the form to update an existing event.
 * @package App\Http\Controllers\Admin\Events
 */
class UpdateHtmxController extends Controller
{
    /**
     * Handle the request to display the event update page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing event.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Event $event The event to be updated.
     * @return View The rendered event update view.
     */
    public function __invoke(FormRequest $request, Event $event): View
    {
        $this->authorize('update', $event);

        $event->fill($request->input());

        $data = [
            'event' => $event,
        ];

        return view('pages.admin.events.update-form', $data);
    }
}
