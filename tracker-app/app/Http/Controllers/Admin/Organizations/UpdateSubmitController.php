<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Organizations\UpdateRequest;
use App\Models\Organization;
use App\Plugins\PluginForms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing organization.
 */
class UpdateSubmitController extends PageController
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
    public function __invoke(
        UpdateRequest $request,
        Organization $organization,
        PluginForms $plugin_forms,
    ): RedirectResponse {
        $organization->name = $request->validated('name');

        if ($request->canChooseSynchronizer())
        {
            $organization->synchronizer = $request->validated('synchronizer') ?: null;
        }

        DB::transaction(function () use ($organization, $request, $plugin_forms): void
        {
            $organization->save();
            $plugin_forms->saved(
                PluginForms::ORGANIZATION_UPDATE,
                $organization,
                $request->validated(),
            );
        });

        FlashType::updated($organization);

        return redirect()->route('admin.organizations.list');
    }
}
