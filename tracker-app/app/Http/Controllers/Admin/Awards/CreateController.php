<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\PageController;
use App\Messages\Awards\PageData\CreateAwardPageData;
use App\Models\Award;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The create award form, blank, copied from `copy_id`, or set to `organization_id`.
 */
class CreateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Awards'), 'admin.awards.list');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        $this->authorize('create', Award::class);

        $data = CreateAwardPageData::call(
            actor: $request->user(),
            copy_id: $request->integer('copy_id') ?: null,
            organization_id: $request->integer('organization_id') ?: null,
        );

        return Inertia::render('admin/awards/Create', $data);
    }
}
