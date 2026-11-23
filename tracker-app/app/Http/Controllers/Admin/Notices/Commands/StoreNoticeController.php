<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Notices\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notices\Commands\StoreNoticeRequest;
use App\Messages\Notices\Commands\CreateNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class StoreNoticeController extends Controller
{
    public function __invoke(StoreNoticeRequest $request): RedirectResponse
    {
        $notice = CreateNotice::call(
            organization_id: $request->validated('organization_id'),
            title: $request->validated('title'),
            type: $request->validated('type'),
            starts_at: Carbon::parse($request->validated('starts_at')),
            ends_at: Carbon::parse($request->validated('ends_at')),
            message: $request->validated('message'),
        );

        FlashType::created($notice);

        return redirect()->route('admin.notices.index');
    }
}
