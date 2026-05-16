<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the form for updating an existing costume.
 *
 * An invokable controller that authorizes the user, retrieves the costume,
 * and renders the costume update form with available organization assignments.
 */
class UpdateController extends MagicBusController
{
    /**
     * Set up breadcrumbs for the costume update page.
     */
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Costumes', 'admin.costumes.list');
    }

    /**
     * Displays the costume update form.
     *
     * Authorizes the user, loads the costume with its current organizations,
     * retrieves all available organizations (augmented with assignment status),
     * and renders the update form.
     */
    public function __invoke(Request $request, Costume $costume): View
    {
        $this->authorize('update', $costume);

        $costume->load('organizations');

        $assigned_organizations = $costume->organizations->pluck('id')->all();

        $organizations = Organization::ofTypeOrganizations()->orderBy(Organization::NAME)->get();

        foreach ($organizations as $organization)
        {
            $organization->assigned = in_array($organization->id, $assigned_organizations);
        }

        $data = compact('costume', 'organizations');

        return view('pages.admin.costumes.update', $data);
    }
}
