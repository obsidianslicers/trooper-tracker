<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\PageController;
use App\Messages\Awards\PageData\ListAwardsPageData;
use App\Models\Filters\AwardFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Lists the awards the trooper may moderate, by search term and organization.
 */
class ListController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
    }

    public function __invoke(Request $request, AwardFilter $filter): InertiaResponse
    {
        $data = ListAwardsPageData::call(
            actor: $request->user(),
            filter: $filter,
            search_term: $request->query('search_term'),
            organization_id: $request->integer('organization_id') ?: null,
        );

        return Inertia::render('admin/awards/List', $data);
    }
}
