<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Awards\Commands\StoreAwardTrooperRequest;
use App\Messages\Awards\Commands\GrantTrooperAward;
use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class StoreAwardTrooperController extends Controller
{
    public function __invoke(StoreAwardTrooperRequest $request, Award $award): RedirectResponse
    {
        $this->authorize('update', $award);

        $trooper = Trooper::findOrFail($request->validated('trooper_id'));

        GrantTrooperAward::call(
            award: $award,
            trooper: $trooper,
            award_date: Carbon::parse($request->validated('award_date')),
        );

        FlashType::success(__('admin.... and the Award goes to :name', ['name' => $trooper->display_name]));

        return redirect()->route('admin.awards.troopers.index', $award);
    }
}
