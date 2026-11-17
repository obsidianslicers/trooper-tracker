<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateNoticeAsReadRequest;
use App\Messages\Notices\Commands\UpdateNoticeAsRead;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;

/**
 * Marks a notice as read for the signed-in trooper and returns them to the page they were on.
 */
class UpdateNoticeAsReadController extends Controller
{
    public function __invoke(UpdateNoticeAsReadRequest $request, Notice $notice): RedirectResponse
    {
        UpdateNoticeAsRead::call(trooper: $request->user(), notice: $notice);

        return back();
    }
}
