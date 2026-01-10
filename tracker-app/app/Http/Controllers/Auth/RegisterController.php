<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\FlashMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Displays the user registration page.
 *
 * This controller renders the registration form with a list of available
 * organizations and pre-fills data from the OAuth session if the trooper
 * is registering via an OAuth provider.
 */
class RegisterController extends Controller
{
    /**
     * @param FlashMessageService $flash The flash message service.
     */
    public function __construct(
        private readonly FlashMessageService $flash,
    ) {
    }

    /**
     * Handle the incoming request to display the registration form.
     *
     * Retrieves all organizations with their related data and the registration
     * authentication information from the session (if OAuth was used). The
     * registration data includes the email and method (OAuth provider or manual).
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered registration page view with organizations and registration data.
     */
    public function __invoke(Request $request): View
    {
        $organizations = Organization::fullyLoaded()->get();

        foreach ($organizations as $organization)
        {
            $organization->selected = old("organizations.{$organization->id}.selected") === '1';

            $organization->region_id = old("organizations.{$organization->id}.region_id");
            $organization->unit_id = old("organizations.{$organization->id}.unit_id");
        }

        $registration_auth = Session::get('registration_auth');

        $email = old('email', $registration_auth['email'] ?? null);

        $account_type = old('account_type', $request->input('account_type', 'member'));

        $registration_method = old('registration_method', $registration_auth['method'] ?? 'email');

        $organization_hierarchy = $organizations->map(fn($org) => [
            'id' => $org->id,
            'name' => $org->name,
            'selected' => $org->selected,
            'region_id' => $org->region_id,
            'unit_id' => $org->unit_id,
            'regions' => $org->organizations->map(fn($region) =>
                [
                    'id' => $region->id,
                    'name' => $region->name,
                    'units' => $region->organizations->map(fn($unit) =>
                        [
                            'id' => $unit->id,
                            'name' => $unit->name,
                        ]),
                ]),
        ]);

        $data = compact(
            'organizations',
            'organization_hierarchy', 'email', 'registration_method', 'account_type'
        );

        return view('pages.auth.register', $data);
    }
}
