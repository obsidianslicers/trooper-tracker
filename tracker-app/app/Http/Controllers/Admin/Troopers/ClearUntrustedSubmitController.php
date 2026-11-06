<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Troopers\Commands\ClearTrooperUntrusted;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClearUntrustedSubmitController extends Controller
{
    public function __invoke(Request $request, Trooper $trooper): RedirectResponse
    {
        $this->authorize('markUntrusted', $trooper);

        ClearTrooperUntrusted::call(trooper: $trooper);

        FlashType::success("{$trooper->display_name} is no longer marked do not trust.");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
