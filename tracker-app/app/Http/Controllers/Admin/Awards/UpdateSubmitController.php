<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Awards\UpdateRequest;
use App\Models\Award;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the submission of the form for updating an existing award.
 */
class UpdateSubmitController extends PageController
{
    /**
     * Handle the request to update an award.
     *
     * Validates the request, updates the award's properties, saves it,
     * and then redirects with a success message.
     *
     * @param  UpdateRequest  $request  The validated request containing the updated data.
     * @param  Award  $award  The award to be updated.
     * @return RedirectResponse A redirect response to the awards list.
     */
    public function __invoke(UpdateRequest $request, Award $award): RedirectResponse
    {
        $award->name = $request->validated('name');

        $award->save();

        FlashType::updated($award);

        return redirect()->route('admin.awards.list');
    }
}
