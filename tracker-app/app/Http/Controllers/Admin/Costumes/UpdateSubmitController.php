<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Costumes\UpdateRequest;
use App\Models\Costume;
use App\Models\OrganizationCostume;
use Illuminate\Http\RedirectResponse;

/**
 * Handles submission of the form for updating an existing costume.
 *
 * An invokable controller that validates the request, updates the costume's
 * name, and redirects to the costume list page.
 */
class UpdateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update a costume.
     *
     * Validates the request, updates the costume's name, saves it,
     * and redirects to the costume list with a success message.
     *
     * @param  UpdateRequest  $request  The validated request containing the updated data.
     * @param  Costume  $costume  The costume to be updated (route model binding).
     * @return RedirectResponse A redirect response to the costume list.
     */
    public function __invoke(UpdateRequest $request, Costume $costume): RedirectResponse
    {
        $costume->name = $request->validated('name');
        $costume->save();

        $current_organization_ids = $costume->organizations->pluck('id')->all();
        $selected_organization_ids = $request->selected_organization_ids();

        // Delete organizations that are no longer selected
        $organization_ids_to_remove = array_diff($current_organization_ids, $selected_organization_ids);
        foreach ($organization_ids_to_remove as $organization_id)
        {
            OrganizationCostume::query()
                ->where(OrganizationCostume::ORGANIZATION_ID, $organization_id)
                ->where(OrganizationCostume::COSTUME_ID, $costume->id)
                ->delete();
        }

        // For newly selected organizations, restore if soft-deleted or create if new
        $organization_ids_to_add = array_diff($selected_organization_ids, $current_organization_ids);

        foreach ($organization_ids_to_add as $organization_id)
        {
            $record = OrganizationCostume::withTrashed()
                ->where(OrganizationCostume::ORGANIZATION_ID, $organization_id)
                ->where(OrganizationCostume::COSTUME_ID, $costume->id)
                ->first();

            if ($record && $record->trashed())
            {
                $record->restore();
            }
            elseif ($record == null)
            {
                OrganizationCostume::create([
                    OrganizationCostume::ORGANIZATION_ID => $organization_id,
                    OrganizationCostume::COSTUME_ID => $costume->id,
                ]);
            }
        }

        $this->flash->updated($costume);

        return redirect()->route('admin.costumes.list');
    }
}
