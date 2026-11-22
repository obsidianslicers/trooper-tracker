<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\PageData\Admin\ListTroopersPageData;
use App\Models\Filters\TrooperFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The troopers a Command Staff member looks after: administrators see everyone, others the
 * troopers in the organizations they moderate.
 */
class ListController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
    }

    public function __invoke(Request $request, TrooperFilter $filter): Response
    {
        $data = ListTroopersPageData::call(
            actor: $request->user(),
            filter: $filter,
            sort_col: $request->string('sort_col')->toString() ?: null,
            sort_dir: $request->string('sort_dir')->toString() ?: null,
            search_term: $request->string('search_term')->toString() ?: null,
            membership_role: $request->string('membership_role')->toString() ?: null,
            organization_id: $request->integer('organization_id') ?: null,
        );

        return Inertia::render('admin/troopers/List', $data);
    }
}
