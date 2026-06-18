<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\VoidTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoidSubmitController extends MagicBusController
{
    public function __invoke(Request $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('void', $trooper);

        $this->bus->send(new VoidTrooperCommand($trooper));

        $this->flash->success("{$trooper->display_name} has been marked as created in error.");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
