<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\TrooperCostume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Removes a costume from the trooper's profile via HTMX.
 *
 * This controller follows the Action-Domain-Responder (ADR) pattern:
 * - **Action (Controller):** Receives HTMX request with costume_id
 * - **Domain (Models):** Soft-deletes TrooperCostume record for authenticated trooper
 * - **Responder:** Returns costumes table HTML fragment for HTMX swap
 *
 * Security:
 * - Scopes deletion to authenticated trooper's costumes only (prevents unauthorized deletion)
 * - Verifies costume belongs to trooper before deleting
 *
 * Uses soft deletes so costume can be restored if needed (e.g., if trooper re-adds it).
 * The attachCostume() method in the Submit controller will restore soft-deleted records.
 *
 * This enables dynamic costume removal without full page reload.
 */
class CostumesDeleteHtmxController extends Controller
{
    /**
     * Remove a costume from the authenticated trooper's profile.
     *
     * Validates costume_id is provided, finds the TrooperCostume record scoped to
     * the authenticated trooper, and soft-deletes it. Returns updated costume table HTML.
     *
     * @param Request $request The incoming HTTP request containing costume_id.
     * @return View The costumes table partial for HTMX replacement.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $costume_id = (int) $request->get('costume_id', -1);

        if ($costume_id > -1)
        {
            \Illuminate\Support\Facades\Log::info("Removing costume ID {$costume_id} from trooper ID {$trooper->id}");

            $trooper_costume = $trooper->trooper_costumes()
                ->where(TrooperCostume::ID, $costume_id)
                ->first();

            if ($trooper_costume !== null)
            {
                \Illuminate\Support\Facades\Log::info("Calling Delete");
                $trooper_costume->delete();
            }
        }

        $trooper_costumes = $trooper->trooper_costumes()->with('organization_costume.organization')->get();

        $data = compact('trooper_costumes');

        return view('pages.account.costumes-table', $data);
    }
}
