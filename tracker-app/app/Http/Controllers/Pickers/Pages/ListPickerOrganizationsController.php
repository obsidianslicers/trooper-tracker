<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pickers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Organizations\PageData\ListPickerOrganizationsPageData;
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
class ListPickerOrganizationsController extends PageController
{
    public function __invoke(Request $request): View
    {
        if ($request->query('property') === null)
        {
            throw new Exception('Missing property parameter');
        }

        $data = ListPickerOrganizationsPageData::call($request, ['query' => $request->query()]);

        return view('pickers.organization', $data);
    }
}
