<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Account\PageData\DeniedPageData;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Display the denied membership page for a denied trooper.
 *
 * Shows the trooper their denial reason and allows them to resubmit
 * membership applications for other organizations.
 *
 * This controller uses the DeniedPageData message to aggregate
 * all necessary queries and prepare form state.
 */
class DeniedController extends PageController
{
    /**
     * Display the denied membership page.
     *
     * Redirects to index or pending if trooper is not denied.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View|RedirectResponse The rendered denied page or redirect
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $trooper = $request->user();

        if (!$trooper->is_denied)
        {
            return redirect()->route($trooper->is_pending ? 'account.pending' : 'account.index');
        }

        $data = DeniedPageData::call(trooper: $trooper);

        return view('pages.account.denied', $data);
    }
}
