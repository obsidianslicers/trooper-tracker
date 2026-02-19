<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Costumes\UpdateRequest;
use App\Models\Costume;
use Illuminate\Http\RedirectResponse;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing costume.
 */
class UpdateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update an costume
     *
     * Validates the request, updates the costume's name, saves it,
     * and then redirects with a success message.
     *
     * @param  UpdateRequest  $request  The validated request containing the updated data
     * @param  Costume  $costume  The costume to be updated
     * @return RedirectResponse A redirect response to the costume list
     */
    public function __invoke(UpdateRequest $request, Costume $costume): RedirectResponse
    {
        $costume->name = $request->validated('name');

        $costume->save();

        $this->flash->updated($costume);

        return redirect()->route('admin.costumes.list');
    }
}
