<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the form for updating an existing costume.
 *
 * An invokable controller that authorizes the user, retrieves the costume,
 * and renders the costume update form with appropriate breadcrumbs.
 */
class UpdateController extends MagicBusController
{
    /**
     * Set up breadcrumbs for the costume update page.
     *
     * @return void
     */
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Costumes', 'admin.costumes.list');
    }

    /**
     * Handle the request to display the costume update page.
     *
     * Authorizes the user, retrieves the costume, and renders the costume
     * update form.
     *
     * @param Costume $costume The costume to be updated (route model binding).
     * @return View The rendered costume update view.
     */
    public function __invoke(Request $request, Costume $costume): View
    {
        $this->authorize('update', $costume);

        $data = compact('costume');

        return view('pages.admin.costumes.update', $data);
    }
}
