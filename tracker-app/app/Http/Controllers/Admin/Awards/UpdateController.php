<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Models\Award;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the form to update an existing award.
 *
 * Displays the award update form allowing administrators and moderators to
 * modify the award's properties (name, frequency, organization).
 */
class UpdateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Awards', 'admin.awards.list');
    }

    /**
     * Handle the request to display the award update page
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing award.
     *
     * @param  Request  $request  The incoming HTTP request object
     * @param  Award  $award  The award to be updated
     * @return View The rendered award update view
     */
    public function __invoke(Request $request, Award $award): View
    {
        $this->authorize('update', $award);

        $data = compact('award');

        return view('pages.admin.awards.update', $data);
    }
}
