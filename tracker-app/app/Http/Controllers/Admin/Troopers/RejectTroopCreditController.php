<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\RejectTroopCreditRequest;
use App\Messages\Events\Commands\Verification\RejectTroopCredit;
use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class RejectTroopCreditController extends Controller
{
    public function __invoke(
        RejectTroopCreditRequest $request,
        EventTrooper $event_trooper,
        Organization $organization,
    ): RedirectResponse {
        RejectTroopCredit::call(event_trooper: $event_trooper, organization_id: $organization->id);

        FlashType::success("Removed {$organization->name} credit.");

        return redirect()->back();
    }
}
