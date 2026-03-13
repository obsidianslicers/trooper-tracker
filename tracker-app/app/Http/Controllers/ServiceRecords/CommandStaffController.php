<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Troopers\Queries\GetCommandStaffQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the command staff listing.
 */
class CommandStaffController extends MagicBusController
{
    /**
     * Retrieves command staff troopers and renders the command staff view.
     *
     * @throws \RuntimeException
     */
    public function __invoke(Request $request): View
    {
        $query = new GetCommandStaffQuery;

        $troopers = $this->bus->send($query);

        $data = compact('troopers');

        return view('pages.service-records.command-staff', $data);
    }
}
