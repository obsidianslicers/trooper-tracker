<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes\Pages;

use App\Http\Controllers\PageController;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays the admin page for folding a duplicate costume into another.
 */
class ShowMergeCostumesFormController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Costumes'), 'admin.costumes.index');
    }

    public function __invoke(): InertiaResponse
    {
        return Inertia::render('admin/costumes/MergeCostumes', []);
    }
}
