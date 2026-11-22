<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Awards\CreateRequest;
use App\Messages\Awards\Commands\CreateAward;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends Controller
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $award = CreateAward::call(
            organization_id: (int) $request->validated('organization_id'),
            name: $request->validated('name'),
            frequency: AwardFrequency::from($request->validated('frequency')),
        );

        FlashType::created($award);

        return redirect()->route('admin.awards.update', compact('award'));
    }
}
