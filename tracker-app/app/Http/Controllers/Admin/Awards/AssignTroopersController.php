<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class AssignTroopersController
 *
 * Handles displaying the form to assign an award to troopers.
 * @package App\Http\Controllers\Admin\Awards
 */
class AssignTroopersController extends Controller
{
    /**
     * AssignTroopersController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Awards', 'admin.awards.list');
    }

    /**
     * Handle the request to display the award assignment page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view containing
     * the form to assign the award to troopers.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Award $award The award to assign.
     * @return View The rendered award assignment view.
     */
    public function __invoke(Request $request, Award $award): View
    {
        $this->authorize('update', $award);

        $this->crumbs->addRoute($award->name, 'admin.awards.list-troopers', ['award' => $award]);

        $search = $request->get('search');

        $query = Trooper::whereHas('organizations', function ($query) use ($award) {
            $query->whereRaw('tt_organizations.id = ?', [$award->organization_id]);
        })->with('awards')->orderBy('name');

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
            $troopers = $query->get();
        } else {
            $troopers = $query->paginate(50);
        }

        $data = compact('award', 'troopers', 'search');

        return view('pages.admin.awards.assign-troopers', $data);
    }
}