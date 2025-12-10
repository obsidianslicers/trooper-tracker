<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Filters\AwardFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class ListController
 *
 * Handles the display of the main awards list in the admin section.
 * This controller fetches and displays a paginated list of awards, which can be
 * filtered by scope (active, past, future) and by organization. It ensures that
 * non-administrator users can only see awards for organizations they moderate.
 * @package App\Http\Controllers\Admin\Awards
 */
class ListController extends Controller
{
    /**
     * ListController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the request to display the awards list page.
     *
     * Sets up breadcrumbs and retrieves a paginated list of awards.
     * The list is filtered to show only active awards. If an 'organization_id'
     * is provided in the request, the list is further filtered to that organization.
     * Non-administrator users will only see awards for organizations they moderate.
     *
     * @param Request $request The incoming HTTP request object.
     * @return View The rendered view for the awards list.
     */
    public function __invoke(Request $request, AwardFilter $filter): View
    {
        $organization = $this->getOrganization($request);

        $awards = $this->getAwards($request, $filter);

        $data = [
            'awards' => $awards,
            'organization' => $organization,
        ];

        return view('pages.admin.awards.list', $data);
    }

    /**
     * Retrieves the organization from the request if an 'organization_id' is provided.
     *
     * @param Request $request The incoming HTTP request.
     * @return Organization|null The found Organization or null if no ID is provided.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    private function getOrganization(Request $request): ?Organization
    {
        if ($request->has('organization_id'))
        {
            $organization_id = $request->query('organization_id');

            return Organization::findOrFail($organization_id);
        }

        return null;
    }

    /**
     * Builds and executes the query to retrieve a paginated list of awards.
     *
     * The query is built based on the requested scope (active, past, future), an
     * optional organization filter, and the user's authorization level (admin vs. moderator).
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The authenticated trooper.
     * @param Organization|null $organization The organization to filter by, if any.
     * @return LengthAwarePaginator The paginated list of awards.
     */
    private function getAwards(Request $request, AwardFilter $filter): LengthAwarePaginator
    {
        $trooper = $request->user();

        $q = Award::with([
            'organization.trooper_assignments' => function ($q) use ($trooper)
            {
                $q->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                    ->where(TrooperAssignment::IS_MODERATOR, true);
            }
        ]);

        $q = $q->filterWith($filter)->moderatedBy($trooper);

        return $q->paginate(15)->withQueryString();
    }
}
