<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pickers;

use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Filters\TrooperFilter;
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
class TrooperPickerController extends MagicBusController
{
    /**
     * Handle the incoming request to display the trooper picker view
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  TrooperFilter  $filter  The filter service for applying query constraints
     * @return View The rendered trooper picker view
     *
     * @throws Exception If property parameter is missing
     */
    public function __invoke(Request $request, TrooperFilter $filter): View
    {
        $trooper = $request->user();

        $property = $request->query('property');

        $search_term = $request->query('search_term');

        if ($property === null)
        {
            throw new Exception('Missing property parameter');
        }

        $organization_id = $request->query('organization_id');

        if ($organization_id !== null)
        {
            $organization_id = (int) $organization_id;
        }

        $query = new GetTroopersForPickerQuery($trooper, $filter, $request->query());

        $troopers = $this->bus->send($query);

        $event_name = $request->query('event', '');

        $data = compact('troopers', 'property', 'search_term', 'organization_id', 'event_name');

        return view('pickers.trooper', $data);
    }
}
