<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ResubmitDeniedRequest;
use App\Messages\Troopers\Commands\ResubmitDeniedTrooper;
use Illuminate\Http\RedirectResponse;

/**
 * Handle form submission for resubmitting a denied membership application.
 *
 * Calls the ResubmitDeniedTrooper command to update trooper membership requests
 * and flash a success message.
 */
class DeniedResubmitController extends Controller
{
    /**
     * Handle the resubmit denied membership request.
     *
     * @param  ResubmitDeniedRequest  $request  The validated resubmit request
     * @return RedirectResponse Redirect to pending memberships page
     */
    public function __invoke(ResubmitDeniedRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        ResubmitDeniedTrooper::call(
            trooper: $trooper,
            organizations: $request->validated('organizations', []),
        );

        FlashType::success('Your application has been resubmitted. You will receive an email when a decision is made.');

        return redirect()->route('account.pending');
    }
}
