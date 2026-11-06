<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\PageController;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays the admin page for folding a duplicate costume into another.
 */
class MergeCostumesController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Costumes', 'admin.costumes.list');
    }

    public function __invoke(): InertiaResponse
    {
        return Inertia::render('admin/costumes/MergeCostumes', []);
    }
}
