<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Notices;

use App\Http\Controllers\PageController;
use App\Messages\Notices\PageData\NoticeFormPageData;
use App\Models\Notice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UpdateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Notices'), 'admin.notices.list');
    }

    public function __invoke(Request $request, Notice $notice): InertiaResponse
    {
        $this->authorize('update', $notice);

        $notice->load('organization');

        $data = NoticeFormPageData::call(actor: $request->user(), notice: $notice);

        return Inertia::render('admin/notices/Update', $data);
    }
}
