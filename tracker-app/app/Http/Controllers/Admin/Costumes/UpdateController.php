<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class UpdateController
 *
 * Handles displaying the form to update an existing costume.
 */
class UpdateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Costumes', 'admin.costumes.list');
    }

    /**
     * Handle the request to display the costume update page
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing costume.
     *
     * @param  Request  $request  The incoming HTTP request object
     * @param  Costume  $costume  The costume to be updated
     * @return View The rendered costume update view
     */
    public function __invoke(Request $request, Costume $costume): View
    {
        $this->authorize('update', $costume);

        $data = [
            'costume' => $costume,
        ];

        return view('pages.admin.costumes.update', $data);
    }
}
