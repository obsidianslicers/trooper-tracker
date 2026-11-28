<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Commands\DenyTroopCorrectionRequest;
use App\Messages\Events\Commands\Corrections\DenyTroopCorrection;
use App\Models\TroopCorrection;
use Illuminate\Http\RedirectResponse;

/**
 * The event coordinator says a trooper who never signed up wasn't at their event; moderators look next.
 */
class DenyTroopCorrectionController extends Controller
{
    public function __invoke(DenyTroopCorrectionRequest $request, TroopCorrection $troop_correction): RedirectResponse
    {
        DenyTroopCorrection::call(troop_correction: $troop_correction, coordinator: $request->user());

        FlashType::success(__('events.Thanks. A moderator will take a look.'));

        return redirect()->route('events.troop-correction', $troop_correction);
    }
}
