<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\MarkUntrustedRequest;
use App\Messages\Troopers\Commands\MarkTrooperUntrusted;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;

class MarkTrooperUntrustedController extends Controller
{
    public function __invoke(MarkUntrustedRequest $request, Trooper $trooper): RedirectResponse
    {
        MarkTrooperUntrusted::call(
            trooper: $trooper,
            reason: $request->validated('untrusted_reason'),
            review_costumes: $request->boolean('review_costumes'),
        );

        FlashType::success("{$trooper->display_name} is marked do not trust.");

        return redirect()->route('admin.troopers.profile', compact('trooper'));
    }
}
