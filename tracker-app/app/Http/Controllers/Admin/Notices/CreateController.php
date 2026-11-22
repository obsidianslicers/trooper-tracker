<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Notices;

use App\Http\Controllers\PageController;
use App\Messages\Notices\PageData\CreateNoticePageData;
use App\Models\Notice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The create notice form, blank, copied from `copy_id`, or set to `organization_id`.
 */
class CreateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Notices'), 'admin.notices.list');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        $this->authorize('create', Notice::class);

        $data = CreateNoticePageData::call(
            actor: $request->user(),
            copy_id: $request->integer('copy_id') ?: null,
            organization_id: $request->integer('organization_id') ?: null,
        );

        return Inertia::render('admin/notices/Create', $data);
    }
}
