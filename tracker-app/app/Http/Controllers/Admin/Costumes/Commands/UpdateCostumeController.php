<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Costumes\Commands\UpdateCostumeRequest;
use App\Messages\Costumes\Commands\UpdateCostume;
use App\Models\Costume;
use Illuminate\Http\RedirectResponse;

class UpdateCostumeController extends Controller
{
    public function __invoke(UpdateCostumeRequest $request, Costume $costume): RedirectResponse
    {
        $costume = UpdateCostume::call(
            costume: $costume,
            name: $request->validated('name'),
            organization_ids: $request->selected_organization_ids(),
        );

        FlashType::updated($costume);

        return redirect()->route('admin.costumes.index');
    }
}
