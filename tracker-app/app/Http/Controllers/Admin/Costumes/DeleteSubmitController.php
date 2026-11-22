<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Costumes\Commands\DeleteCostume;
use App\Models\Costume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteSubmitController extends Controller
{
    public function __invoke(Request $request, Costume $costume): RedirectResponse
    {
        $this->authorize('delete', $costume);

        abort_if($costume->countsAsHandler(), 403, 'This costume cannot be deleted.');

        $costume_name = $costume->name;

        DeleteCostume::call(costume: $costume);

        FlashType::success(__("admin.Deleted costume ':name'", ['name' => $costume_name]));

        return redirect()->route('admin.costumes.list');
    }
}
