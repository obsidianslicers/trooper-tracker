<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Http\Controllers\MagicBusController;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class ListController
 *
 * Handles the display of the main troopers list in the admin section.
 * This controller fetches and displays a list of troopers, filtering the results
 * based on the authenticated user's role. Administrators see all troopers, while
 * other roles see only the troopers they are assigned to moderate.
 */
class ListController extends MagicBusController
{
    private const SORT_COLS = [
        Trooper::DISPLAY_NAME => 'Name / Email',
        Trooper::MEMBERSHIP_ROLE => 'Role',
        Trooper::MEMBERSHIP_STATUS => 'Status',
        Trooper::LAST_ACTIVE_AT => 'Last Seen',
    ];

    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the incoming request to display the troopers list page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  TrooperFilter  $filter  The filter service for applying query constraints
     * @return View|RedirectResponse The rendered dashboard page view or a redirect response
     */
    public function __invoke(Request $request, TrooperFilter $filter): View|RedirectResponse
    {
        $trooper = $request->user();

        $sort_col = array_key_exists($request->query('sort_col'), self::SORT_COLS)
            ? $request->query('sort_col')
            : Trooper::DISPLAY_NAME;

        $sort_dir = in_array($request->query('sort_dir'), ['asc', 'desc'])
            ? $request->query('sort_dir')
            : 'asc';

        $organizations = Organization::moderatedBy($trooper)->orderBy(Organization::SEQUENCE)->get();
        $organization_id = $request->query('organization_id');
        $selected_organization = $organization_id ? $organizations->find($organization_id) : null;

        $data = [
            'troopers' => $this->getTroopers($request, $filter, $sort_col, $sort_dir),
            'membership_role' => $request->query('membership_role'),
            'search_term' => $request->query('search_term'),
            'sort_headers' => $this->buildSortHeaders($sort_col, $sort_dir),
            'organizations' => $organizations,
            'organization_id' => $organization_id,
            'selected_organization' => $selected_organization,
        ];

        return view('pages.admin.troopers.list', $data);
    }

    /**
     * Get the collection of troopers to be displayed.
     *
     * @param  Request  $request  The incoming HTTP request, containing potential filters.
     * @param  TrooperFilter  $filter  The filter service for applying query constraints.
     * @param  string  $sort_col  The column to sort by.
     * @param  string  $sort_dir  The sort direction ('asc' or 'desc').
     * @return LengthAwarePaginator A paginated list of troopers.
     */
    private function getTroopers(
        Request $request,
        TrooperFilter $filter,
        string $sort_col,
        string $sort_dir
    ): LengthAwarePaginator {
        $trooper = $request->user();

        $q = Trooper::moderatedBy($trooper)
            ->when(! $trooper->is_administrator, fn ($q) => $q->where(Trooper::MEMBERSHIP_STATUS, '!=', MembershipStatus::INVALID->value))
            ->orderBy($sort_col, $sort_dir);

        return $q->filterWith($filter)->paginate(15)->withQueryString();
    }

    /**
     * Build pre-computed sort header data for each sortable column.
     *
     * Each entry contains the label, icon class, and toggle URL for that column.
     * Clicking the active column toggles direction; clicking another column sorts it ASC.
     *
     * @param  string  $sort_col  The currently active sort column.
     * @param  string  $sort_dir  The currently active sort direction.
     * @return array<string, array{label: string, icon: string, url: string}>
     */
    private function buildSortHeaders(string $sort_col, string $sort_dir): array
    {
        $headers = [];

        foreach (self::SORT_COLS as $col => $label)
        {
            $active = $sort_col === $col;
            $next_dir = ($active && $sort_dir === 'asc') ? 'desc' : 'asc';

            $headers[$col] = [
                'label' => $label,
                'icon' => $active ? ($sort_dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort',
                'url' => route('admin.troopers.list', qs(['sort_col' => $col, 'sort_dir' => $next_dir, 'page' => 1])),
            ];
        }

        return $headers;
    }
}
