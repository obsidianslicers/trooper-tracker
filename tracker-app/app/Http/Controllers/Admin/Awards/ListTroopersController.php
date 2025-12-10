<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class UpdateController
 *
 * Handles displaying the form to update an existing award.
 * @package App\Http\Controllers\Admin\Awards
 */
class ListTroopersController extends Controller
{
    /**
     * UpdateController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Awards', 'admin.awards.list');
    }

    /**
     * Handle the request to display the award update page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing award.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Award $award The award to be updated.
     * @return View The rendered award update view.
     */
    public function __invoke(Request $request, Award $award): View
    {
        $this->authorize('update', $award);

        $troopers = $award->troopers()->orderByDesc(AwardTrooper::AWARD_DATE)->get();

        $data = compact('award', 'troopers');

        return view('pages.admin.awards.list-troopers', $data);
    }
}
