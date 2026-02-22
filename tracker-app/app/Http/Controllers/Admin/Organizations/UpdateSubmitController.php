<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Organizations\UpdateRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing organization.
 */
class UpdateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update an organization
     *
     * Validates the request, updates the organization's name, saves it,
     * and then redirects with a success message.
     *
     * @param  UpdateRequest  $request  The validated request containing the updated data
     * @param  Organization  $organization  The organization to be updated
     * @return RedirectResponse A redirect response to the organization list
     */
    public function __invoke(UpdateRequest $request, Organization $organization): RedirectResponse
    {
        $organization->name = $request->validated('name');
        $organization->sync_sheet_id = $request->validated('sync_sheet_id');

        $organization->save();

        $this->flash->updated($organization);

        return redirect()->route('admin.organizations.list');
    }
}
