<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pickers;

use App\Features\Organizations\Queries\GetOrganizationsForPickerQuery;
use App\Http\Controllers\MagicBusController;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles requests for a view that allows picking an organization.
 *
 * This controller is designed to be used for UI components where a user
 * needs to select an organization from a list, potentially filtered by their
 * moderation permissions.
 */
class OrganizationPickerController extends MagicBusController
{
    /**
     * Handle the incoming request to display the organization picker view
     *
     * If the 'moderatored_only' query parameter is present, it fetches only the
     * organizations the authenticated user is assigned to moderate. Otherwise,
     * it returns an empty list.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered organization picker view
     *
     * @throws Exception If property parameter is missing
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $property = $request->query('property');

        if ($property === null)
        {
            throw new Exception('Missing property parameter');
        }

        $query = new GetOrganizationsForPickerQuery($trooper, $request->query());

        $organizations = $this->bus->send($query);

        $data = compact('organizations', 'property');

        return view('pickers.organization', $data);
    }
}
