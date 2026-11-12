<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\Queries\Verification\GetTroopsAwaitingVerification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TroopVerificationsController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(Request $request): View
    {
        $event_troopers = GetTroopsAwaitingVerification::call(moderator: $request->user());

        return view('pages.admin.troopers.troop-verifications', compact('event_troopers'));
    }
}
