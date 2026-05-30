<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\MagicBusController;
use App\Models\TrooperCostume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the authenticated trooper's profile management page.
 *
 * This controller renders the profile page where troopers can view and
 * manage their personal information, contact details, and account settings.
 */
class ProfileController extends MagicBusController
{
    /**
     * Handle the incoming request to display the profile page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered profile page view
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $trooper->load([
            'trooper_costumes.organization_costume.costume',
            'trooper_costumes.organization_costume.organization',
            'organizations',
        ]);

        $costumeOptions = $trooper->trooper_costumes->mapWithKeys(function (TrooperCostume $tc) use ($trooper) {
            $oc = $tc->organization_costume;
            $org = $trooper->organizations->firstWhere('id', $oc?->organization_id);
            $identifier = $org?->pivot?->identifier ?? '';
            $prefix = $oc?->prefix ?? '';
            $label = $prefix.$identifier.' — '.($oc?->costume?->name ?? '').' ('.($oc?->organization?->name ?? '').')';

            return [$tc->id => $label];
        });

        $data = compact('trooper', 'costumeOptions');

        return view('pages.account.profile', $data);
    }
}
