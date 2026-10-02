<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Account\PageData\PendingMembershipPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PendingMembershipController extends Controller
{
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        if (!$trooper->is_pending)
        {
            return redirect()->route('account.index');
        }

        $data = PendingMembershipPageData::call($request);

        return Inertia::render('account/PendingMembership', $data);
    }
}
