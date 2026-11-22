<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\PageController;
use App\Messages\Organizations\PageData\ListOrganizationsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ListController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        return Inertia::render(
            'admin/organizations/List',
            ListOrganizationsPageData::call(actor: $request->user())
        );
    }
}
