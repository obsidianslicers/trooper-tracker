<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\UnvoidTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnvoidSubmitController extends MagicBusController
{
    public function __invoke(Request $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('unvoid', $trooper);

        $this->bus->send(new UnvoidTrooperCommand($trooper));

        $this->flash->success("{$trooper->display_name} has been restored to pending status.");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
