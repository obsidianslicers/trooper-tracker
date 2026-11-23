<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\CommandStaffPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the command staff listing.
 */
class ShowCommandStaffController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = CommandStaffPageData::call($request);

        return view('pages.service-records.command-staff', $data);
    }
}
