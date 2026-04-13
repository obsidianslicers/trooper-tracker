<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class ListController
 *
 * Handles the display of the main costumes list in the admin section.
 * This controller fetches and displays a list of costumes with their assignment
 * status for the authenticated user.
 */
class ListController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the request to display the costumes list page
     *
     * Sets up breadcrumbs, retrieves costumes with assignment data for the
     * authenticated user, and returns the list view.
     *
     * @param  Request  $request  The incoming HTTP request object
     * @return View The rendered costumes list view
     */
    public function __invoke(Request $request): View
    {
        $relations = ['organizations' => function ($query) {
            $query->orderBy(Organization::NAME);
        }];

        $costumes = Costume::with($relations)->orderBy(Costume::NAME)->get();

        $data = compact('costumes');

        return view('pages.admin.costumes.list', $data);
    }
}
