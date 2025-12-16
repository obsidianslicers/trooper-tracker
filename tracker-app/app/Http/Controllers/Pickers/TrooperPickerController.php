<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pickers;

use App\Http\Controllers\Controller;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
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
class TrooperPickerController extends Controller
{
    /**
     * Handle the incoming request to display the trooper picker view.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered organization picker view.
     */
    public function __invoke(Request $request, TrooperFilter $filter): View
    {
        $trooper = $request->user();

        $property = $request->query('property');

        $search_term = $request->query('search_term');

        if ($property === null)
        {
            throw new Exception("Missing property parameter");
        }

        $troopers = collect([]);

        if ($filter->hasFilter())
        {
            $troopers = Trooper::filterWith($filter)->active()->orderBy(Trooper::NAME)->get();
        }

        $data = compact('troopers', 'property', 'search_term');

        return view('pickers.trooper', $data);
    }
}
