<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\PageController;
use App\Messages\Costumes\PageData\UpdateCostumePageData;
use App\Models\Costume;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The costume form with the delete confirmation open, saying what deleting it would change.
 */
class DeleteController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Costumes'), 'admin.costumes.list');
    }

    public function __invoke(Request $request, Costume $costume): InertiaResponse
    {
        $this->authorize('delete', $costume);

        abort_if($costume->countsAsHandler(), 403, 'This costume cannot be deleted.');

        return Inertia::render(
            'admin/costumes/Update',
            UpdateCostumePageData::call(costume: $costume, confirm_delete: true)
        );
    }
}
