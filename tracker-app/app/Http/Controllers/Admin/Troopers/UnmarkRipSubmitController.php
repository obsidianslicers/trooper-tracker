<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Messages\Troopers\Commands\UnmarkTrooperRip;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnmarkRipSubmitController extends PageController
{
    public function __invoke(Request $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('unmarkRip', $trooper);

        UnmarkTrooperRip::call(trooper: $trooper);

        FlashType::success("{$trooper->display_name} has been restored to pending status.");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
