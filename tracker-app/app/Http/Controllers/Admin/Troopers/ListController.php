<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class ListController
 *
 * Handles the display of the main troopers list in the admin section.
 * This controller fetches and displays a list of troopers, filtering the results
 * based on the authenticated user's role. Administrators see all troopers, while
 * other roles see only the troopers they are assigned to moderate.
 * @package App\Http\Controllers\Admin\Troopers
 */
class ListController extends Controller
{
    /**
     * ListController constructor.
     *
     * @param BreadCrumbService $crumbs The breadcrumb service for managing navigation history.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the incoming request to display the troopers list page.
     *
     * Sets up breadcrumbs, retrieves the appropriate list of troopers based on the
     * user's role, and returns the main troopers display view.
     *
     * @param Request $request The incoming HTTP request.
     * @return View|RedirectResponse The rendered dashboard page view or a redirect response.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $troopers = $this->getTroopers($request);

        $data = [
            'troopers' => $troopers,
            'membership_role' => $request->query('membership_role'),
            'search_term' => $request->query('search_term')
        ];

        return view('pages.admin.troopers.list', $data);
    }

    /**
     * Get the collection of troopers to be displayed.
     *
     * This method filters troopers based on the request's query parameters, such as
     * 'membership_role' and 'search_term'. If the authenticated user is not an
     * Administrator, the results are further constrained to only troopers moderated
     * by the current user.
     * @param Request $request The incoming HTTP request, containing potential filters.
     * @return LengthAwarePaginator A paginated list of troopers.
     */
    private function getTroopers(Request $request): LengthAwarePaginator
    {
        $trooper = $request->user();

        $q = Trooper::orderBy(Trooper::NAME);

        if ($request->has('membership_role'))
        {
            $membership_role = MembershipRole::from($request->query('membership_role'));

            $q = $q->where(Trooper::MEMBERSHIP_ROLE, $membership_role);
        }

        if ($request->has('search_term') && strlen($request->query('search_term', '')) > 3)
        {
            $search_term = '%' . $request->query('search_term') . '%';

            $q = $q->where(function ($query) use ($search_term)
            {
                $query->where(Trooper::EMAIL, 'like', $search_term)
                    ->orWhere(Trooper::USERNAME, 'like', $search_term)
                    ->orWhere(Trooper::NAME, 'like', $search_term);
            });
        }

        if (!$trooper->isAdministrator())
        {
            $q = $q->moderatedBy($trooper);
        }

        return $q->paginate(15)->withQueryString();
    }
}
