<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Awards\RemoveTrooperRequest;
use App\Models\Award;
use App\Models\AwardTrooper;
use Illuminate\Http\RedirectResponse;

/**
 * Handles removing an award from a specific trooper.
 */
class RemoveTrooperController extends MagicBusController
{
    /**
     * Handle the request to remove an award from a trooper.
     */
    public function __invoke(RemoveTrooperRequest $request, Award $award): RedirectResponse
    {
        $this->authorize('update', $award);

        $award_trooper_id = $request->validated('remove_trooper_id');

        $award_trooper = AwardTrooper::query()
            ->with('trooper:id,display_name')
            ->where(AwardTrooper::ID, $award_trooper_id)
            ->where(AwardTrooper::AWARD_ID, $award->id)
            ->first();

        if ($award_trooper !== null)
        {
            $trooper_name = $award_trooper->trooper?->display_name ?? 'trooper';

            $award_trooper->delete();

            $this->flash->success('Removed award from '.$trooper_name);
        }

        return redirect()->route('admin.awards.list-troopers', $award);
    }
}
