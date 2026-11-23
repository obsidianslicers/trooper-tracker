<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pickers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\PageData\ListPickerTroopersPageData;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles requests for a view that allows picking a trooper.
 *
 * This controller is designed to be used for UI components where a user
 * needs to select a trooper from a list, potentially filtered by organization
 * or search criteria.
 */
class ListPickerTroopersController extends PageController
{
    public function __invoke(Request $request): View
    {
        if ($request->query('property') === null)
        {
            throw new Exception('Missing property parameter');
        }

        $data = ListPickerTroopersPageData::call($request, ['query' => $request->query()]);

        return view('pickers.trooper', $data);
    }
}
