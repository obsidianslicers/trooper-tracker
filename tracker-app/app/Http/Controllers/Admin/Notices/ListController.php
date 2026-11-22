<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Notices;

use App\Http\Controllers\PageController;
use App\Messages\Notices\PageData\ListNoticesPageData;
use App\Models\Filters\NoticeFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Lists the notices the trooper may moderate, by scope (active, past, future) and organization.
 */
class ListController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
    }

    public function __invoke(Request $request, NoticeFilter $filter): InertiaResponse
    {
        $data = ListNoticesPageData::call(
            actor: $request->user(),
            filter: $filter,
            scope: (string) $request->query('scope', 'active'),
            organization_id: $request->integer('organization_id') ?: null,
        );

        return Inertia::render('admin/notices/List', $data);
    }
}
