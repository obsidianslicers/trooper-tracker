<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\PageData\Admin\RecruitTrooperPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The recruit page where a moderator can directly add any active trooper to one of their
 * moderated units.
 *
 * Authorization is gated on the organization at submission time, not on the trooper being
 * added, which bypasses the circular moderatedBy dependency.
 */
class RecruitController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Troopers'), 'admin.troopers.list');
    }

    public function __invoke(Request $request): Response
    {
        return Inertia::render(
            'admin/troopers/Recruit',
            RecruitTrooperPageData::call(actor: $request->user()),
        );
    }
}
