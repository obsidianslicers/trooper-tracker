<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Messages\Troopers\Commands\RemoveCostumeFromTrooper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\RemoveCostumeRequest;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for deleting a costume from the authenticated trooper's profile.
 *
 * This controller validates the request, dispatches the necessary command to delete the costume,
 * and redirects back to the update profile page.
 */
class RemoveCostumeController extends Controller
{
    /**
     * Handle the incoming request to delete a costume from the trooper's profile.
     *
     * @param  RemoveCostumeRequest  $request  The validated request to delete a costume
     * @return  InertiaResponse|SymfonyResponse Redirect to the update profile page with success message
     */
    public function __invoke(RemoveCostumeRequest $request): InertiaResponse|SymfonyResponse
    {
        RemoveCostumeFromTrooper::call(
            trooper: $request->user(),
            costume_id: $request->validated('costume_id')
        );

        return Inertia::render('account/Index');
    }
}
