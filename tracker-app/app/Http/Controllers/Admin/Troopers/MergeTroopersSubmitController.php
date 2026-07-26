<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Troopers\MergeTroopersRequest;
use App\Jobs\MergeTroopersJob;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles the submission of the merge troopers form for admin users.
 *
 * This controller processes the merge troopers form submission, ensuring that the
 * request is validated and the appropriate actions are taken to merge troopers.
 */
class MergeTroopersSubmitController extends Controller
{
    /**
     * Handle the incoming request to submit the merge troopers form.
     *
     * Validates the request and performs the necessary actions to merge troopers.
     *
     * @param  MergeTroopersRequest  $request  The validated merge troopers form request
     * @return InertiaResponse|SymfonyResponse The merge troopers page view or redirect to home if authenticated
     */
    public function __invoke(MergeTroopersRequest $request): InertiaResponse|SymfonyResponse
    {
        $source_trooper = Trooper::findorfail($request->validated('source_trooper_id'));
        $target_trooper = Trooper::findorfail($request->validated('target_trooper_id'));

        dispatch(new MergeTroopersJob($source_trooper, $target_trooper));

        return redirect()->route('admin.troopers.merge')->withSuccess('Troopers are currently being merged.');
    }
}
