<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Models\Costume;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main costumes list in the admin section.
 *
 * Retrieves and displays all costumes with their organization assignments,
 * ordered alphabetically.
 */
class ListController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Displays the costumes list.
     *
     * Retrieves all costumes with their assigned organizations (ordered by name)
     * and renders the costumes list view.
     */
    public function __invoke(Request $request): View
    {
        $relations = ['organizations' => function ($query) {
            $query->orderBy(Organization::NAME);
        }];

        $costumes = Costume::with($relations)->orderBy(Costume::NAME)->get();

        $data = compact('costumes');

        return view('pages.admin.costumes.list', $data);
    }
}
