<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\Admin\ListAdminEventsPageData;
use App\Models\Filters\EventFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The events a Command Staff member manages, filterable by status, organization and name.
 * Administrators see every event; moderators the events of organizations they moderate.
 */
class ListController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
    }

    public function __invoke(Request $request, EventFilter $filter): Response
    {
        $data = ListAdminEventsPageData::call(
            actor: $request->user(),
            filter: $filter,
            status: $request->string('status')->toString() ?: null,
            search_term: $request->string('search_term')->toString() ?: null,
            organization_id: $request->integer('organization_id') ?: null,
        );

        return Inertia::render('admin/events/List', $data);
    }
}
