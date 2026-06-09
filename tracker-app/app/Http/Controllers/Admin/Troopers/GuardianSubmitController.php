<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Troopers\GuardianRequest;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

class GuardianSubmitController extends MagicBusController
{
    public function __invoke(GuardianRequest $request, Trooper $trooper): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('guardian_email', $data)) {
            $guardian_email = $data['guardian_email'];
            $data[Trooper::GUARDIAN_ID] = $guardian_email
                ? Trooper::where(Trooper::EMAIL, $guardian_email)->value(Trooper::ID)
                : null;
            unset($data['guardian_email']);
        }

        $this->bus->send(new UpdateTrooperCommand($trooper, $data));

        $this->flash->updated($trooper);

        return redirect()->route('admin.troopers.guardian', compact('trooper'));
    }
}
