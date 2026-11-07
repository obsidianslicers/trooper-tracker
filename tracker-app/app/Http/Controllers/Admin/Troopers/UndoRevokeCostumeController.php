<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\UndoRevokeCostumeRequest;
use App\Messages\Troopers\Commands\Costumes\UndoRevokeTrooperCostume;
use App\Models\TrooperCostume;
use Illuminate\Http\RedirectResponse;

class UndoRevokeCostumeController extends Controller
{
    public function __invoke(UndoRevokeCostumeRequest $request, TrooperCostume $trooper_costume): RedirectResponse
    {
        UndoRevokeTrooperCostume::call(trooper_costume: $trooper_costume);

        FlashType::success('Revoke undone. The costume is approved again.');

        return redirect()->back();
    }
}
