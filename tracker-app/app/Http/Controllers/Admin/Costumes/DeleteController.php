<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the confirmation page for deleting a costume.
 *
 * Verifies deletion is permitted and calculates referential counts before
 * displaying the deletion confirmation form.
 */
class DeleteController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Costumes', 'admin.costumes.list');
    }

    /**
     * Displays the costume deletion confirmation.
     *
     * Authorizes deletion, verifies the costume is not used as a handler,
     * counts referential records (event troopers and trooper costumes),
     * and renders the confirmation view.
     */
    public function __invoke(Request $request, Costume $costume): View
    {
        $this->authorize('delete', $costume);

        abort_if($costume->countsAsHandler(), 403, 'This costume cannot be deleted.');

        $org_costume_ids = OrganizationCostume::query()
            ->where(OrganizationCostume::COSTUME_ID, $costume->id)
            ->pluck(OrganizationCostume::ID);

        $event_trooper_count = EventTrooper::query()
            ->where(EventTrooper::COSTUME_ID, $costume->id)
            ->orWhere(EventTrooper::BACKUP_COSTUME_ID, $costume->id)
            ->count();

        $trooper_costume_count = TrooperCostume::query()
            ->whereIn(TrooperCostume::ORGANIZATION_COSTUME_ID, $org_costume_ids)
            ->count();

        return view('pages.admin.costumes.delete', compact('costume', 'event_trooper_count', 'trooper_costume_count'));
    }
}
