<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Plugins\PluginClubSynchronizers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class UpdateController
 *
 * Handles displaying the form to update an existing organization.
 */
class UpdateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Organizations', 'admin.organizations.list');
    }

    /**
     * Handle the request to display the organization update page
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing organization.
     *
     * @param  Request  $request  The incoming HTTP request object
     * @param  Organization  $organization  The organization to be updated
     * @return View The rendered organization update view
     */
    public function __invoke(
        Request $request,
        Organization $organization,
        PluginClubSynchronizers $synchronizers,
    ): View {
        $this->authorize('update', $organization);

        $synchronizer_options = $this->synchronizerOptions($synchronizers, $organization);
        $data = compact('organization', 'synchronizer_options');

        return view('pages.admin.organizations.update', $data);
    }

    /**
     * A club whose sync comes from a plugin that is now off keeps its choice, so
     * saving the page does not clear it.
     *
     * @return array<string, string>
     */
    private function synchronizerOptions(
        PluginClubSynchronizers $synchronizers,
        Organization $organization,
    ): array {
        $options = $synchronizers->options();
        $current = $organization->synchronizer;

        if ($current !== null && !isset($options[$current]))
        {
            $options[$current] = "{$current} (not available)";
        }

        return $options;
    }
}
