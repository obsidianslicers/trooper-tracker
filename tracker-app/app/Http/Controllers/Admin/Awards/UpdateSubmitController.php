<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Awards\UpdateRequest;
use App\Messages\Awards\Commands\RenameAward;
use App\Models\Award;
use Illuminate\Http\RedirectResponse;

class UpdateSubmitController extends Controller
{
    public function __invoke(UpdateRequest $request, Award $award): RedirectResponse
    {
        $award = RenameAward::call(award: $award, name: $request->validated('name'));

        FlashType::updated($award);

        return redirect()->route('admin.awards.list');
    }
}
