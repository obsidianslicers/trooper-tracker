<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Commands\ConfirmTroopCorrectionRequest;
use App\Messages\Events\Commands\Corrections\ConfirmTroopCorrection;
use App\Models\TroopCorrection;
use Illuminate\Http\RedirectResponse;

/**
 * The event coordinator confirms a trooper who never signed up was at their event.
 */
class ConfirmTroopCorrectionController extends Controller
{
    public function __invoke(ConfirmTroopCorrectionRequest $request, TroopCorrection $troop_correction): RedirectResponse
    {
        ConfirmTroopCorrection::call(troop_correction: $troop_correction, coordinator: $request->user());

        FlashType::success(__('events.Thanks. The troop now counts for them.'));

        return redirect()->route('events.troop-correction', $troop_correction);
    }
}
