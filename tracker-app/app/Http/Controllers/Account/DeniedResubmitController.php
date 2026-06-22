<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\ResubmitDeniedTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ResubmitDeniedRequest;
use Illuminate\Http\RedirectResponse;

class DeniedResubmitController extends MagicBusController
{
    public function __invoke(ResubmitDeniedRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        $this->bus->send(new ResubmitDeniedTrooperCommand(
            $trooper,
            $request->validated('organizations', []),
        ));

        $this->flash->success('Your application has been resubmitted. You will receive an email when a decision is made.');

        return redirect()->route('account.pending');
    }
}
