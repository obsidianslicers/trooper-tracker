<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Troopers\Queries\GetTrooperCostumesQuery;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Displays a trooper's service record dashboard.
 */
class TrooperController extends MagicBusController
{
    /**
     * Retrieves dashboard data and renders the trooper service record view.
     *
     * Filters command staff and handler costumes from the displayed costume list.
     *
     * @throws \RuntimeException
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        if ($trooper->id == Auth::user()->id)
        {
            $this->crumbs->addRoute('Profile', 'account.profile');
        }

        $service_record_query = new GetTrooperServiceRecordQuery($trooper->id);

        $data = $this->bus->send($service_record_query);

        $trooper_costumes_query = new GetTrooperCostumesQuery($data['trooper']);

        $trooper_costumes = $this->bus->send($trooper_costumes_query);

        $trooper_costumes = $trooper_costumes->filter(fn ($c) => !in_array($c->name, [Costume::COMMAND_STAFF, Costume::HANDLER]));

        $data['trooper_costumes'] = $trooper_costumes;

        return view('pages.service-records.trooper', $data);
    }
}
