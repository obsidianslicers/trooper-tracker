<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\Commands\ApproveTroopCreditRequest;
use App\Messages\Events\Commands\Verification\ApproveTroopCredit;
use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class ApproveTroopCreditController extends Controller
{
    public function __invoke(
        ApproveTroopCreditRequest $request,
        EventTrooper $event_trooper,
        Organization $organization,
    ): RedirectResponse {
        ApproveTroopCredit::call(event_trooper: $event_trooper, organization_id: $organization->id);

        FlashType::success(__('admin.Approved :organization credit.', ['organization' => $organization->name]));

        return redirect()->back();
    }
}
