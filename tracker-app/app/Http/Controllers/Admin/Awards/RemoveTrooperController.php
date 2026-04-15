<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Models\Award;
use App\Models\AwardTrooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles removing an award from a specific trooper.
 */
class RemoveTrooperController extends MagicBusController
{
    /**
     * Handle the request to remove an award from a trooper.
     *
     * @param  Request  $request
     * @param  Award  $award
     * @return RedirectResponse
     */
    public function __invoke(Request $request, Award $award): RedirectResponse
    {
        $this->authorize('update', $award);

        $award_trooper_id = $request->input('remove_trooper_id')
            ?? $request->input('award_trooper_id');

        if ($award_trooper_id === null)
        {
            return redirect()->route('admin.awards.list-troopers', $award);
        }

        $award_trooper = AwardTrooper::query()
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
