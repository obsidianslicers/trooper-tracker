<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class CreateController
 *
 * Handles displaying the form to create a new costume .
 */
class CreateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Costumes', 'admin.costumes.list');
    }

    /**
     * Handle the request to display the costume creation page
     *
     * @param  Request  $request  The incoming HTTP request object
     * @return View The rendered costume creation view
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('create', Costume::class);

        $costume = new Costume;

        $data = compact('costume');

        return view('pages.admin.costumes.create', $data);
    }
}
