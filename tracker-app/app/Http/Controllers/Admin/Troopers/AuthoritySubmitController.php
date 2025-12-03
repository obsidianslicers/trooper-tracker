<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\AuthorityRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class AuthoritySubmitController
 *
 * Handles the form submission for a trooper's authority settings, including their
 * membership role and which organizations they are assigned to moderate.
 * @package App\Http\Controllers\Admin\Troopers
 */
class AuthoritySubmitController extends Controller
{
    /**
     * AuthoritySubmitController constructor.
     *
     * @param FlashMessageService $flash The service for creating flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update a trooper's authority settings.
     *
     * This method validates the request, updates the trooper's membership role,
     * syncs their organization moderation assignments, and then redirects to the
     * main trooper list.
     *
     * @param AuthorityRequest $request The validated form request.
     * @param Trooper $trooper The trooper whose authorities are being updated.
     * @return RedirectResponse A redirect response to the trooper list page.
     */
    public function __invoke(AuthorityRequest $request, Trooper $trooper): RedirectResponse
    {
        $trooper->membership_role = $request->validated('membership_role');

        $trooper->save();

        $this->updateModeratedOrganizations($request->validated('moderators', []), $trooper);

        $this->flash->updated($trooper);

        return redirect()->route('admin.troopers.list');
    }

    /**
     * Updates the organizations a trooper is assigned to moderate.
     *
     * This method first resets all of the trooper's moderator assignments. Then, if
     * the trooper's role is set to Moderator, it iterates through the provided
     * organization IDs and creates or updates assignments to mark them as a moderator.
     *
     * @param array $moderated_organizations An array from the request, where keys are organization IDs.
     * @param Trooper $trooper The trooper whose assignments are being updated.
     */
    private function updateModeratedOrganizations(array $moderated_organizations, Trooper $trooper): void
    {
        //  reset all the moderatored organizations to "false"
        $trooper->trooper_assignments()
            ->update([TrooperAssignment::MODERATOR => false]);

        if ($trooper->isModerator())
        {
            //  not update ones that were selected as "true"
            foreach ($moderated_organizations as $organization_id => $data)
            {
                $selected = filter_var($data['selected'], FILTER_VALIDATE_BOOLEAN) == true;

                $trooper_assignment = $trooper->trooper_assignments()
                    ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id)
                    ->first();

                if ($trooper_assignment == null)
                {
                    $trooper_assignment = new TrooperAssignment();

                    $trooper_assignment->organization_id = $organization_id;
                    $trooper_assignment->trooper_id = $trooper->id;
                }

                $trooper_assignment->moderator = $selected;

                $trooper_assignment->save();
            }
        }
    }
}
