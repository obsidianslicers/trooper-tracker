<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Awards\UpdateRequest;
use App\Models\Award;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing award.
 * @package App\Http\Controllers\Admin\Awards
 */
class UpdateSubmitController extends Controller
{
    /**
     * UpdateSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update a award.
     *
     * Validates the request, updates the award's properties, saves it,
     * and then redirects with a success message.
     *
     * @param UpdateRequest $request The validated request containing the updated data.
     * @param Award $award The award to be updated.
     * @return RedirectResponse A redirect response to the awards list.
     */
    public function __invoke(UpdateRequest $request, Award $award): RedirectResponse
    {
        $award->name = $request->validated('name');

        $award->save();

        $this->flash->updated($award);

        return redirect()->route('admin.awards.list');
    }
}
