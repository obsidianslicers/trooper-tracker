<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\CancelAccountDeletionCommand;
use App\Http\Controllers\MagicBusController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cancels a pending account deletion request during the 30-day grace period.
 */
class DeletionCancelController extends MagicBusController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $trooper = $request->user();

        $this->bus->send(new CancelAccountDeletionCommand($trooper));

        $this->flash->success('Account deletion cancelled. Your account is no longer scheduled for deletion.');

        return redirect()->route('account.profile');
    }
}
