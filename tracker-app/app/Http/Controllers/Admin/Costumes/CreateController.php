<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\PageController;
use App\Models\Costume;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Costumes'), 'admin.costumes.list');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        $this->authorize('create', Costume::class);

        return Inertia::render('admin/costumes/Create');
    }
}
