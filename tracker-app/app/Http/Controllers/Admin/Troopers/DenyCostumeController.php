<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\DenyCostumeRequest;
use App\Messages\Troopers\Commands\Costumes\DenyTrooperCostume;
use App\Models\TrooperCostume;
use Illuminate\Http\RedirectResponse;

class DenyCostumeController extends Controller
{
    public function __invoke(DenyCostumeRequest $request, TrooperCostume $trooper_costume): RedirectResponse
    {
        DenyTrooperCostume::call(trooper_costume: $trooper_costume);

        FlashType::success('Costume request denied.');

        return redirect()->back();
    }
}
