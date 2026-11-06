<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\Queries\GetCommandStaff;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Displays the command staff listing.
 */
class CommandStaffController extends PageController
{
    /**
     * Retrieves command staff troopers and renders the command staff view.
     *
     * @throws RuntimeException
     */
    public function __invoke(Request $request): View
    {
        $troopers = GetCommandStaff::call();

        $data = compact('troopers');

        return view('pages.service-records.command-staff', $data);
    }
}
