<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Costumes\CreateRequest;
use App\Models\Costume;
use Illuminate\Http\RedirectResponse;

/**
 * Handles submission of the form for creating a new costume.
 *
 * An invokable controller that validates the request, creates a new costume,
 * and redirects to the update page with a success message.
 */
class CreateSubmitController extends MagicBusController
{
    public function __invoke(CreateRequest $request, Costume $parent): RedirectResponse
    {
        $costume = new Costume;

        $costume->name = $request->validated('name');

        $costume->save();

        $this->flash->created($costume);

        return redirect()->route('admin.costumes.update', compact('costume'));
    }
}
