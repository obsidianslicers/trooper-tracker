<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\ApproveCostumeRequest;
use App\Messages\Troopers\Commands\Costumes\ApproveTrooperCostume;
use App\Models\TrooperCostume;
use Illuminate\Http\RedirectResponse;

class ApproveCostumeController extends Controller
{
    public function __invoke(ApproveCostumeRequest $request, TrooperCostume $trooper_costume): RedirectResponse
    {
        ApproveTrooperCostume::call(trooper_costume: $trooper_costume);

        FlashType::success('Costume approved.');

        return redirect()->back();
    }
}
