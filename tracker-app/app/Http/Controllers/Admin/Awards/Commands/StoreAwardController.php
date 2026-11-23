<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards\Commands;

use App\Enums\AwardFrequency;
use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Awards\Commands\StoreAwardRequest;
use App\Messages\Awards\Commands\CreateAward;
use Illuminate\Http\RedirectResponse;

class StoreAwardController extends Controller
{
    public function __invoke(StoreAwardRequest $request): RedirectResponse
    {
        $award = CreateAward::call(
            organization_id: (int) $request->validated('organization_id'),
            name: $request->validated('name'),
            frequency: AwardFrequency::from($request->validated('frequency')),
        );

        FlashType::created($award);

        return redirect()->route('admin.awards.edit', compact('award'));
    }
}
