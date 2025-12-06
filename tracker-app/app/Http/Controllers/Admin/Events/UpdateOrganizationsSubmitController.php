<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Events\UpdateOrganizationsRequest;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\Organization;
use App\Services\FlashMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles the display of the authenticated user's notification settings page.
 */
class UpdateOrganizationsSubmitController extends Controller
{
    /**
     * UpdateController constructor.
     *
     * @param FlashMessageService $flash The service for managing breadcrumbs.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to display the notification settings.
     *
     * This method retrieves the notification preferences and organizational
     * notification subscriptions for the currently authenticated user and
     * renders the corresponding view.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered notification settings view.
     */
    public function __invoke(UpdateOrganizationsRequest $request, Event $event): RedirectResponse
    {
        foreach ($request->input('organizations', []) as $organization_id => $data)
        {
            EventOrganization::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'organization_id' => $organization_id,
                ],
                [
                    'can_attend' => $data['can_attend'] ?? false,
                    'troopers_allowed' => $data['troopers_allowed'] ?? null,
                    'handlers_allowed' => $data['handlers_allowed'] ?? null,
                ]
            );
        }

        $data = [
            'event' => $event
        ];

        $this->flash->updated($event);

        return redirect()->route('admin.events.organizations', $data);
    }
}
