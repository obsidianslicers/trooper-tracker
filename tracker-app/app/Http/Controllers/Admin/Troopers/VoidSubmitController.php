<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\MagicBusController;
use App\Messages\Troopers\Commands\VoidTrooper;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoidSubmitController extends MagicBusController
{
    public function __invoke(Request $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('void', $trooper);

        VoidTrooper::call(trooper: $trooper);

        FlashType::success("{$trooper->display_name} has been marked as created in error.");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
