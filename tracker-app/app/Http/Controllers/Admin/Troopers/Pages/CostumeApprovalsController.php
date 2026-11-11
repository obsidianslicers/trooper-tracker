<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\Queries\Costumes\GetPendingTrooperCostumes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CostumeApprovalsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(Request $request): View
    {
        $trooper_costumes = GetPendingTrooperCostumes::call(moderator: $request->user());

        return view('pages.admin.troopers.costume-approvals', compact('trooper_costumes'));
    }
}
