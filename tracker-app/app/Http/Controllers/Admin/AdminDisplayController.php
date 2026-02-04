<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main administration dashboard.
 *
 * This controller provides a summary of administrative tasks, such as displaying
 * the count of troopers pending approval and setting a relevant flash message.
 */
class AdminDisplayController extends MagicBusController
{
    /**
     * Handle the incoming request to display the admin dashboard
     *
     * It calculates the number of troopers pending approval, sets a corresponding
     * flash message, and renders the main admin view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered admin dashboard view or a redirect response
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $not_approved = Trooper::pendingApprovals()->moderatedBy($trooper)->count();

        if ($not_approved == 1)
        {
            $msg = "There is {$not_approved} trooper ready for action!";
        }
        elseif ($not_approved > 1)
        {
            $msg = "There are {$not_approved} troopers ready for action!";
        }

        if ($not_approved > 0)
        {
            $this->flash->warning($msg);
        }

        $data = compact('not_approved');

        return view('pages.admin.display', $data);
    }
}
