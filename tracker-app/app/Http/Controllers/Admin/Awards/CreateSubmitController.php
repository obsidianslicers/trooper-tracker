<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Awards\CreateRequest;
use App\Models\Award;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class CreateSubmitController
 *
 * Handles the submission of the form for creating a new award.
 * @package App\Http\Controllers\Admin\Awards
 */
class CreateSubmitController extends Controller
{
    /**
     * CreateSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to create a new award.
     *
     * Validates the request, creates a new award with the provided data,
     * saves it, and then redirects with a success message.
     *
     * @param CreateRequest $request The validated request containing the new award's data.
     * @return RedirectResponse A redirect response to the awards list.
     */
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $award = new Award();

        $award->organization_id = $request->validated('organization_id');
        $award->name = $request->validated('name');
        $award->frequency = $request->validated('frequency');

        $award->save();

        $this->flash->created($award);

        return redirect()->route('admin.awards.list');
    }
}
