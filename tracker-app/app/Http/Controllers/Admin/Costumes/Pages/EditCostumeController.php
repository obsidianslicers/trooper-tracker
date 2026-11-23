<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Costumes\PageData\UpdateCostumePageData;
use App\Models\Costume;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EditCostumeController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Costumes'), 'admin.costumes.index');
    }

    public function __invoke(Request $request, Costume $costume): InertiaResponse
    {
        $this->authorize('update', $costume);

        return Inertia::render(
            'admin/costumes/Update',
            UpdateCostumePageData::call(costume: $costume)
        );
    }
}
