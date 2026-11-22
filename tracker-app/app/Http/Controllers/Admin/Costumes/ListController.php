<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\PageController;
use App\Messages\Costumes\PageData\ListCostumesPageData;
use App\Models\Filters\CostumeFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ListController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
    }

    public function __invoke(Request $request, CostumeFilter $filter): InertiaResponse
    {
        $data = ListCostumesPageData::call(
            filter: $filter,
            search_term: $request->query('search_term'),
        );

        return Inertia::render('admin/costumes/List', $data);
    }
}
