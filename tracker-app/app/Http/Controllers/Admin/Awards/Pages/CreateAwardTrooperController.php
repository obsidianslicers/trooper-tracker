<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Awards\PageData\AssignAwardTrooperPageData;
use App\Models\Award;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreateAwardTrooperController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Awards'), 'admin.awards.index');
    }

    public function __invoke(Request $request, Award $award): InertiaResponse
    {
        $this->authorize('update', $award);

        return Inertia::render(
            'admin/awards/AssignTrooper',
            AssignAwardTrooperPageData::call(award: $award)
        );
    }
}
