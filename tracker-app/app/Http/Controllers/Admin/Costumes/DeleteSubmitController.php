<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Handles deletion of a costume from the admin interface.
 *
 * Nulls out references on EventTrooper rows (preserving costume_organization_ids
 * so historical troop credit is retained), soft-deletes OrganizationCostume and
 * TrooperCostume child records, then soft-deletes the Costume itself.
 */
class DeleteSubmitController extends MagicBusController
{
    public function __invoke(Request $request, Costume $costume): RedirectResponse
    {
        $this->authorize('delete', $costume);

        abort_if($costume->countsAsHandler(), 403, 'This costume cannot be deleted.');

        $costume_name = $costume->name;

        DB::transaction(function () use ($costume) {
            $org_costume_ids = OrganizationCostume::query()
                ->where(OrganizationCostume::COSTUME_ID, $costume->id)
                ->pluck(OrganizationCostume::ID);

            // Soft-delete trooper costumes linked to this costume's org variants
            TrooperCostume::query()
                ->whereIn(TrooperCostume::ORGANIZATION_COSTUME_ID, $org_costume_ids)
                ->get()
                ->each->delete();

            // Soft-delete the organization costume records
            OrganizationCostume::query()
                ->where(OrganizationCostume::COSTUME_ID, $costume->id)
                ->get()
                ->each->delete();

            // Null out primary costume references on event troopers.
            // Bulk update bypasses the observer so costume_organization_ids
            // (and the historical troop credit they represent) are preserved.
            EventTrooper::query()
                ->where(EventTrooper::COSTUME_ID, $costume->id)
                ->update([EventTrooper::COSTUME_ID => null]);

            EventTrooper::query()
                ->where(EventTrooper::BACKUP_COSTUME_ID, $costume->id)
                ->update([EventTrooper::BACKUP_COSTUME_ID => null]);

            $costume->delete();
        });

        Costume::resequenceAll();

        $this->flash->success("Deleted costume '{$costume_name}'");

        return redirect()->route('admin.costumes.list');
    }
}
