<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Commands\RegisterTrooperRequest;
use App\Messages\Auth\Commands\RegisterTrooper;
use Exception;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles the submission of the trooper registration form.
 *
 * This controller processes the complete registration flow by:
 * - Creating the trooper account with PENDING status
 * - Submitting pending organization join requests
 * - Linking OAuth accounts if applicable
 * - Sending confirmation email
 * - Redirecting to the thank you page
 */
class RegisterTrooperController extends Controller
{
    /**
     * Handle the incoming registration request.
     *
     * Orchestrates the multi-step registration process including trooper creation,
     * organization join requests and email confirmation.
     * The newly created trooper will have PENDING status until approved by an admin.
     *
     * @param  RegisterTrooperRequest  $request  The validated registration form request
     * @return InertiaResponse|SymfonyResponse A redirect to the thank you page after successful registration
     */
    public function __invoke(RegisterTrooperRequest $request): InertiaResponse|SymfonyResponse
    {
        try
        {
            RegisterTrooper::call($request, ['organizations' => $request->validated('organizations', [])]);

            FlashType::success(__(
                'auth.Request submitted successfully! You will receive an e-mail when your request is approved or denied.'
            ));

            return Inertia::location(route('auth.thank-you'));
        }
        catch (Exception $e)
        {
            return back()->withDanger($e->getMessage());
        }
    }
}
