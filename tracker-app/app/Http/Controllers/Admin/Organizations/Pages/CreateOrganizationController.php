<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations\Pages;

use App\Http\Controllers\PageController;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreateOrganizationController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Organizations'), 'admin.organizations.index');
    }

    public function __invoke(Request $request, Organization $parent): InertiaResponse
    {
        $this->authorize('update', $parent);

        return Inertia::render('admin/organizations/Create', [
            'parent' => ['id' => $parent->id, 'name' => $parent->name],
        ]);
    }
}
