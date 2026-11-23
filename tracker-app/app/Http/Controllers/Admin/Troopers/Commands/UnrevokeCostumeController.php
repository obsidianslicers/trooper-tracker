<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\Commands\UnrevokeCostumeRequest;
use App\Messages\Troopers\Commands\Costumes\UnrevokeTrooperCostume;
use App\Models\TrooperCostume;
use Illuminate\Http\RedirectResponse;

class UnrevokeCostumeController extends Controller
{
    public function __invoke(UnrevokeCostumeRequest $request, TrooperCostume $trooper_costume): RedirectResponse
    {
        UnrevokeTrooperCostume::call(trooper_costume: $trooper_costume);

        FlashType::success(__('admin.Revoke undone. The costume is approved again.'));

        return redirect()->back();
    }
}
