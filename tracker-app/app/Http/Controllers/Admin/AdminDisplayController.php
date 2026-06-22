<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MembershipStatus;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminDisplayController extends MagicBusController
{
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $not_approved = Trooper::pendingApprovals()->moderatedBy($trooper)->count();

        $pending_join_requests = TrooperRequest::pending()
            ->whereHas('trooper', function ($query): void {
                $query->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE);
            })
            ->forModerator($trooper)
            ->count();

        return view('pages.admin.display', compact('not_approved', 'pending_join_requests'));
    }
}
