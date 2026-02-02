<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Notices;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Notices\UpdateRequest;
use App\Models\Notice;
use Illuminate\Http\RedirectResponse;

/**
 * Class UpdateSubmitController
 *
 * Handles the submission of the form for updating an existing notice.
 */
class UpdateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update a notice.
     *
     * Validates the request, updates the notice's properties, saves it,
     * and then redirects with a success message.
     *
     * @param  UpdateRequest  $request  The validated request containing the updated data.
     * @param  Notice  $notice  The notice to be updated.
     * @return RedirectResponse A redirect response to the notices list.
     */
    public function __invoke(UpdateRequest $request, Notice $notice): RedirectResponse
    {
        $notice->title = $request->validated('title');
        $notice->type = $request->validated('type');
        $notice->starts_at = $request->validated('starts_at');
        $notice->ends_at = $request->validated('ends_at');
        $notice->message = $request->validated('message');

        $notice->save();

        $this->flash->updated($notice);

        return redirect()->route('admin.notices.list');
    }
}
