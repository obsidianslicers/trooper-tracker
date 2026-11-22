<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Costumes\CreateRequest;
use App\Messages\Costumes\Commands\CreateCostume;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends Controller
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $costume = CreateCostume::call(name: $request->validated('name'));

        FlashType::created($costume);

        return redirect()->route('admin.costumes.update', compact('costume'));
    }
}
