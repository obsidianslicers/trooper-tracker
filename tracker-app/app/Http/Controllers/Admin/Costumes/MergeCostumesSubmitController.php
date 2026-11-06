<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Costumes\MergeCostumesRequest;
use App\Messages\Costumes\Commands\Merge\MergeCostumes;
use App\Models\Costume;
use Illuminate\Http\RedirectResponse;

/**
 * Folds the selected duplicate costume into the costume being kept.
 */
class MergeCostumesSubmitController extends Controller
{
    public function __invoke(MergeCostumesRequest $request): RedirectResponse
    {
        $source_costume = Costume::findOrFail($request->validated('source_costume_id'));
        $target_costume = Costume::findOrFail($request->validated('target_costume_id'));

        MergeCostumes::call(target_costume: $target_costume, source_costume: $source_costume);

        return redirect()
            ->route('admin.costumes.merge')
            ->withSuccess("Merged \"{$source_costume->name}\" into \"{$target_costume->name}\".");
    }
}
