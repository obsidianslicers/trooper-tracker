<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Messages\Troopers\Commands\CancelTrooperDeletion;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Cancels a pending account deletion request during the 30-day grace period.
 */
class CancelDeletionController extends Controller
{
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        CancelTrooperDeletion::call(trooper: $trooper);

        return redirect()->route('account.index')->withSuccess('Your account deletion request has been canceled. Your account will remain active.');
    }
}
