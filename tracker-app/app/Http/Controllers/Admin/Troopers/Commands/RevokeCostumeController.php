<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\RevokeCostumeRequest;
use App\Messages\Troopers\Commands\Costumes\RevokeTrooperCostume;
use App\Models\TrooperCostume;
use Illuminate\Http\RedirectResponse;

class RevokeCostumeController extends Controller
{
    public function __invoke(RevokeCostumeRequest $request, TrooperCostume $trooper_costume): RedirectResponse
    {
        RevokeTrooperCostume::call(trooper_costume: $trooper_costume);

        FlashType::success('Costume approval revoked.');

        return redirect()->back();
    }
}
