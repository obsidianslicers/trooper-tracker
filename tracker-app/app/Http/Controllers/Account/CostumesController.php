<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Costumes\Queries\GetCostumesPickerQuery;
use App\Features\Organizations\Queries\GetOrganizationCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the costume management page for the authenticated trooper.
 *
 * This controller retrieves available costumes from organizations the trooper
 * belongs to and displays their currently assigned costumes. Troopers can add
 * or remove costumes dynamically via HTMX.
 */
class CostumesController extends MagicBusController
{
    /**
     * Display the costume management page for the authenticated trooper.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered costume management page
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organization_ids = $trooper->organizations()->pluck('organization_id')->toArray();

        $costumes_query = new GetCostumesPickerQuery($organization_ids);

        $costumes = $this->bus->send($costumes_query);

        $trooper_costumes_query = new GetTrooperCostumesQuery($trooper);

        $trooper_costumes = $this->bus->send($trooper_costumes_query);

        $data = compact('trooper_costumes', 'costumes');

        return view('pages.account.costumes', $data);
    }
}
