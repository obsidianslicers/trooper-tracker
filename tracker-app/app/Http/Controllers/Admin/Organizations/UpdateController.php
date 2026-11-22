<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\PageController;
use App\Messages\Organizations\PageData\UpdateOrganizationPageData;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UpdateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Organizations'), 'admin.organizations.list');
    }

    public function __invoke(Request $request, Organization $organization): InertiaResponse
    {
        $this->authorize('update', $organization);

        return Inertia::render(
            'admin/organizations/Update',
            UpdateOrganizationPageData::call(actor: $request->user(), organization: $organization)
        );
    }
}
