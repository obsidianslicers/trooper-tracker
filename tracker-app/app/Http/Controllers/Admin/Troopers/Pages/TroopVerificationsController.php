<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\PageData\Reviews\TroopVerificationsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TroopVerificationsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
    }

    public function __invoke(Request $request): Response
    {
        return Inertia::render(
            'admin/troopers/TroopVerifications',
            TroopVerificationsPageData::call(actor: $request->user()),
        );
    }
}
