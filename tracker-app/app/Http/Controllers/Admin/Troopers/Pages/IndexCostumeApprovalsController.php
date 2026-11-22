<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\PageData\Reviews\CostumeApprovalsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexCostumeApprovalsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
    }

    public function __invoke(Request $request): Response
    {
        return Inertia::render(
            'admin/troopers/CostumeApprovals',
            CostumeApprovalsPageData::call(actor: $request->user()),
        );
    }
}
