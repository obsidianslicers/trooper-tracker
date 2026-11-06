<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Account\ResubmitDeniedRequest;
use App\Messages\Troopers\Commands\ResubmitDeniedTrooper;
use Illuminate\Http\RedirectResponse;

class DeniedResubmitController extends PageController
{
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
