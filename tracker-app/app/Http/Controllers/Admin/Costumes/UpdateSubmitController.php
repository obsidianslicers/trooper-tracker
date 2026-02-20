<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Costumes\UpdateRequest;
use App\Models\Costume;
use Illuminate\Http\RedirectResponse;

/**
 * Handles submission of the form for updating an existing costume.
 *
 * An invokable controller that validates the request, updates the costume's
 * name, and redirects to the costume list page.
 */
class UpdateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update a costume.
     *
     * Validates the request, updates the costume's name, saves it,
     * and redirects to the costume list with a success message.
     *
     * @param  UpdateRequest  $request  The validated request containing the updated data.
     * @param  Costume  $costume  The costume to be updated (route model binding).
     * @return RedirectResponse A redirect response to the costume list.
     */
    public function __invoke(UpdateRequest $request, Costume $costume): RedirectResponse
    {
        $costume->name = $request->validated('name');

        $costume->save();

        $this->flash->updated($costume);

        return redirect()->route('admin.costumes.list');
    }
}
