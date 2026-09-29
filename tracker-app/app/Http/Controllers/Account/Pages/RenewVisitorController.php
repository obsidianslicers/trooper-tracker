<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Account\PageData\RenewVisitorPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Displays the visitor access renewal page.
 *
 * Shown when an authenticated visitor trooper's 6-month access window has elapsed.
 * The trooper must explicitly submit a renewal request to re-enter the approvals queue.
 */
class RenewVisitorController extends Controller
{
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $data = RenewVisitorPageData::call($request);

        return Inertia::render('account/RenewVisitor', $data);
    }
}
