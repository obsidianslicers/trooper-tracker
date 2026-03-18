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

        if ($request->has('sync_sheet_id'))
        {
            $organization->sync_sheet_id = $request->validated('sync_sheet_id');
        }

        if ($request->has('discord_mention'))
        {
            $organization->discord_mention = $request->validated('discord_mention');
        }

        if ($request->has(Organization::RELATED_FORUM))
        {
            $organization->related_forum = $request->validated(Organization::RELATED_FORUM);
        }

        if ($request->has(Organization::RELATED_FORUM_ARCHIVE))
        {
            $organization->related_forum_archive = $request->validated(Organization::RELATED_FORUM_ARCHIVE);
        }

        if ($request->has(Organization::XENFORO_GROUP_ACTIVE_ID))
        {
            $organization->xenforo_group_active_id = $request->validated(Organization::XENFORO_GROUP_ACTIVE_ID);
        }

        if ($request->has(Organization::XENFORO_GROUP_RESERVE_ID))
        {
            $organization->xenforo_group_reserve_id = $request->validated(Organization::XENFORO_GROUP_RESERVE_ID);
        }

        if ($request->has(Organization::XENFORO_GROUP_RETIRED_ID))
        {
            $organization->xenforo_group_retired_id = $request->validated(Organization::XENFORO_GROUP_RETIRED_ID);
        }

        $organization->save();

        $this->flash->updated($organization);

        return redirect()->route('admin.organizations.list');
    }
}
