<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Organizations\UpdateCostumesRequest;
use App\Models\Organization;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;

/**
 * Class CostumesSubmitController
 *
 * Handles the submission of the form for managing costumes for an organization.
 * @package App\Http\Controllers\Admin\Organizations
 */
class CostumesSubmitController extends Controller
{
    /**
     * CostumesSubmitController constructor.
     *
     * @param FlashMessageService $flash The service for displaying flash messages.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handle the incoming request to update an organization's costumes.
     *
     * Validates the request, updates the organization's costumes, saves it,
     * and then redirects with a success message.
     *
     * @param UpdateCostumesRequest $request The validated request containing the updated data.
     * @param Organization $organization The organization to be updated.
     * @return RedirectResponse A redirect response to the organization list.
     */
    public function __invoke(UpdateCostumesRequest $request, Organization $organization): RedirectResponse
    {
        $costumes = $request->validated('costumes', []);

        foreach ($costumes as $key => $data)
        {
            $organization_costume = $organization->organization_costumes()->findOrFail($key);
            $organization_costume->name = $data['name'];
            $organization_costume->save();
        }

        $this->flash->updated($organization);

        return redirect()->route('admin.organizations.costumes', compact('organization'));
    }
}
