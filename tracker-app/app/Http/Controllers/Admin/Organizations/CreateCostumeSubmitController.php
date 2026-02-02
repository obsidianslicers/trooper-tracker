<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Organizations\CreateCostumeRequest;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Http\RedirectResponse;

/**
 * Class CreateCostumeSubmitController
 *
 * Handles the submission of the form for creating a new costume under a parent organization.
 */
class CreateCostumeSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to create a new costume.
     *
     * Validates the request, creates a new OrganizationCostume record under the given
     * organization, saves it, and redirects to the costumes management page with a
     * success message.
     *
     * @param  CreateCostumeRequest  $request  The validated request containing the new costume's data.
     * @param  Organization  $organization  The organization to create the costume for.
     * @return RedirectResponse A redirect to the organization's costumes management page.
     */
    public function __invoke(CreateCostumeRequest $request, Organization $organization): RedirectResponse
    {
        $organization_costume = new OrganizationCostume;

        $organization_costume->organization_id = $organization->id;
        $organization_costume->name = $request->validated('name');

        $organization_costume->save();

        $this->flash->created($organization_costume);

        return redirect()->route('admin.organizations.costumes', compact('organization'));
    }
}
