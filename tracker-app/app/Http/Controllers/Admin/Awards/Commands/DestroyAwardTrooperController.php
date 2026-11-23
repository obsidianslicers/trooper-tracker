<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Awards\Commands\DestroyAwardTrooperRequest;
use App\Messages\Awards\Commands\RevokeTrooperAward;
use App\Models\Award;
use Illuminate\Http\RedirectResponse;

class DestroyAwardTrooperController extends Controller
{
    public function __invoke(DestroyAwardTrooperRequest $request, Award $award): RedirectResponse
    {
        $this->authorize('update', $award);

        $trooper_name = RevokeTrooperAward::call(
            award: $award,
            award_trooper_id: (int) $request->validated('remove_trooper_id'),
        );

        if ($trooper_name !== null)
        {
            FlashType::success(__('admin.Removed award from :name', ['name' => $trooper_name]));
        }

        return redirect()->route('admin.awards.troopers.index', $award);
    }
}
