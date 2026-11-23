<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Notices\Commands;

use App\Enums\FlashType;
use App\Enums\NoticeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notices\Commands\UpdateNoticeRequest;
use App\Messages\Notices\Commands\UpdateNotice;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class UpdateNoticeController extends Controller
{
    public function __invoke(UpdateNoticeRequest $request, Notice $notice): RedirectResponse
    {
        $notice = UpdateNotice::call(
            notice: $notice,
            title: $request->validated('title'),
            type: NoticeType::from($request->validated('type')),
            starts_at: Carbon::parse($request->validated('starts_at')),
            ends_at: Carbon::parse($request->validated('ends_at')),
            message: $request->validated('message'),
        );

        FlashType::updated($notice);

        return redirect()->route('admin.notices.index');
    }
}
