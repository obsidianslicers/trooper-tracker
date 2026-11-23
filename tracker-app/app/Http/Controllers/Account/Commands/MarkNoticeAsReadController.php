<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\Commands\MarkNoticeAsReadRequest;
use App\Messages\Notices\Commands\MarkNoticeAsRead;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;

/**
 * Marks a notice as read for the signed-in trooper and returns them to the page they were on.
 */
class MarkNoticeAsReadController extends Controller
{
    public function __invoke(MarkNoticeAsReadRequest $request, Notice $notice): RedirectResponse
    {
        MarkNoticeAsRead::call(trooper: $request->user(), notice: $notice);

        return back();
    }
}
