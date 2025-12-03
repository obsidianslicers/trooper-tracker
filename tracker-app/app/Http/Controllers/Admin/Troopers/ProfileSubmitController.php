<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\ProfileRequest;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class ProfileSubmitController
 *
 * Handles the form submission for updating a trooper's profile.
 * @package App\Http\Controllers\Admin\Troopers
 */
class ProfileSubmitController extends Controller
{
    /**
     * ProfileSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for creating flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update a trooper's profile information.
     *
     * This method validates the incoming data using the ProfileRequest, updates the
     * specified trooper's attributes, flashes a success message to the session,
     * and redirects the user back to the main trooper list.
     *
     * @param ProfileRequest $request The validated form request.
     * @param Trooper $trooper The trooper to be updated.
     * @return RedirectResponse A redirect response to the trooper list page.
     */
    public function __invoke(ProfileRequest $request, Trooper $trooper): RedirectResponse
    {
        $trooper->name = $request->name;
        $trooper->email = $request->email;
        $trooper->phone = $request->phone;
        $trooper->membership_status = $request->membership_status;

        $trooper->save();

        $this->flash->updated($trooper);

        return redirect()->route('admin.troopers.list');
    }
}
