<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetAvailableClubsQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the club memberships page for the authenticated trooper.
 *
 * Shows leaf-node organizations the trooper can request to join,
 * excluding ones they're already a member of or have a pending request for.
 */
class ClubMembershipsController extends MagicBusController
{
    /**
     * @param  Request  $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $available_clubs = $this->bus->send(new GetAvailableClubsQuery($trooper));

        $data = compact('available_clubs');

        return view('pages.account.club-memberships', $data);
    }
}
