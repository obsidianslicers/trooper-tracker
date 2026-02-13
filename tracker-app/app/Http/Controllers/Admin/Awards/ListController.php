<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Models\Award;
use App\Models\Filters\AwardFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main awards list in the admin section.
 *
 * Fetches a paginated list of awards and applies filters. Non-administrator
 * troopers can only see awards for organizations they moderate.
 */
class ListController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the request to display the awards list page.
     *
     * Retrieves a paginated list of awards using the requested filters and
     * includes an optional organization context for the view.
     *
     * @param  Request  $request  The incoming HTTP request object.
     * @param  AwardFilter  $filter  The filter service for applying query constraints.
     * @return View The rendered view for the awards list.
     */
    public function __invoke(Request $request, AwardFilter $filter): View
    {
        $organization = $this->getOrganization($request);

        $awards = $this->getAwards($request, $filter);

        $data = compact('awards', 'organization');

        return view('pages.admin.awards.list', $data);
    }

    /**
     * Retrieve the organization from the request if an `organization_id` is provided.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return Organization|null The found Organization or null if no ID is provided.
     *
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
     * Build and execute the query to retrieve a paginated list of awards.
     *
     * Applies the award filter and limits results to awards moderated by the
     * authenticated trooper.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  AwardFilter  $filter  The filter service for applying query constraints.
     * @return LengthAwarePaginator The paginated list of awards.
     */
    private function getAwards(Request $request, AwardFilter $filter): LengthAwarePaginator
    {
        $trooper = $request->user();

        $q = Award::with([
            'organization.trooper_assignments' => function ($q) use ($trooper) {
                $q->where(TrooperAssignment::TROOPER_ID, $trooper->id)
                    ->where(TrooperAssignment::IS_MODERATOR, true);
            },
        ]);

        $q = $q->withCount('troopers');

        $q = $q->filterWith($filter)->moderatedBy($trooper);

        return $q->paginate(15)->withQueryString();
    }
}
