<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Awards\UpdateRequest;
use App\Models\Award;
use Illuminate\Http\RedirectResponse;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing award.
 */
class UpdateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update a award
     *
     * Validates the request, updates the award's properties, saves it,
     * and then redirects with a success message.
     *
     * @param  UpdateRequest  $request  The validated request containing the updated data
     * @param  Award  $award  The award to be updated
     * @return RedirectResponse A redirect response to the awards list
     */
    public function __invoke(UpdateRequest $request, Award $award): RedirectResponse
    {
        $award->name = $request->validated('name');

        $award->save();

        $this->flash->updated($award);

        return redirect()->route('admin.awards.list');
    }
}
