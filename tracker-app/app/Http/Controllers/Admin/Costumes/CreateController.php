<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the form for creating a new costume.
 *
 * An invokable controller that authorizes the user and renders the costume
 * creation form with appropriate breadcrumbs.
 */
class CreateController extends MagicBusController
{
    /**
     * Set up breadcrumbs for the costume creation page.
     */
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Costumes', 'admin.costumes.list');
    }

    /**
     * Displays the costume creation form.
     *
     * Authorizes the authenticated trooper, initializes a new Costume instance,
     * and renders the creation form.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('create', Costume::class);

        $costume = new Costume;

        $data = compact('costume');

        return view('pages.admin.costumes.create', $data);
    }
}
