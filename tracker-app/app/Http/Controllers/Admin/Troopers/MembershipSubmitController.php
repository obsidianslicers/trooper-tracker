<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\MembershipRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * Handles the display of a trooper's membership management page.
 *
 * Presents organizational memberships and related assignments for a given trooper
 * so administrators and moderators can review or adjust membership details.
 */
class MembershipSubmitController extends Controller
{
    /**
     * Handle the incoming request to display a trooper's authority page.
     *
     * This method authorizes the user, sets up breadcrumbs, and returns the view
     * for managing a specific trooper's roles and organizational assignments.
     *
     * @param MembershipRequest $request The incoming HTTP request.
     * @param Trooper $trooper The trooper whose authorities are to be displayed.
     * @return View|RedirectResponse The rendered authority page view or a redirect response.
     */
    public function __invoke(MembershipRequest $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('update', $trooper);

        $organizations = $request->validated('organizations', []);

        foreach ($organizations as $organization_id => $data)
        {
            $identifier = $data['identifier'] ?? null;

            if ($identifier)
            {
                // syncWithoutDetaching (or upsert) to avoid removing existing
                // memberships not included in the request
                $trooper->organizations()->syncWithoutDetaching([
                    $organization_id => ['identifier' => $identifier],
                ]);

                $assignment_id = $data['assignment'];

                //  FYI - someone can be a member as a "member" or a handler
                $trooper_assignment = $trooper->trooper_assignments()
                    ->where(TrooperAssignment::ORGANIZATION_ID, $assignment_id)
                    ->first();

                if ($trooper_assignment)
                {
                    // update existing assignment
                    $trooper_assignment->is_member = true;
                    $trooper_assignment->save();
                }
                else
                {
                    // create new assignment
                    $trooper->trooper_assignments()->create([
                        TrooperAssignment::ORGANIZATION_ID => $assignment_id,
                        TrooperAssignment::IS_MEMBER => true,
                    ]);
                }
            }
        }

        $data = compact('trooper');

        return redirect()->route('admin.troopers.membership', $data);
    }
}
