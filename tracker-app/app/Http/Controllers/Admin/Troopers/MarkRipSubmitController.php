<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\MarkTrooperRipCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarkRipSubmitController extends MagicBusController
{
    public function __invoke(Request $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('markRip', $trooper);

        $this->bus->send(new MarkTrooperRipCommand($trooper));

        $this->flash->success("{$trooper->display_name} has been marked R.I.P. — In Memoriam.");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
