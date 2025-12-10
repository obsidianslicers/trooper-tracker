<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Enums\AwardType;
use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class CreateController
 *
 * Handles displaying the form to create a new award.
 * @package App\Http\Controllers\Admin\Awards
 */
class CreateController extends Controller
{
    /**
     * CreateController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Awards', 'admin.awards.list');
    }

    /**
     * Handle the request to display the award creation page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view containing
     * the form to create a new award. If a `copy_id` is provided, it pre-populates
     * the form with data from an existing award. If an `organization_id` is
     * provided, it pre-selects that organization. It ensures non-administrators
     * can only interact with awards and organizations they are authorized to moderate.
     *
     * @param Request $request The incoming HTTP request object.
     * @return View The rendered award creation view.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('create', Award::class);

        $trooper = $request->user();

        $award = $this->getAward($request, $trooper);

        $this->assignOrganization($request, $award, $trooper);

        $data = compact('award');

        return view('pages.admin.awards.create', $data);
    }

    /**
     * Creates a new Award instance, optionally copying data from an existing award.
     *
     * If a 'copy_id' is present in the request, it finds the corresponding award
     * (ensuring the user has moderation rights) and copies its properties to a new
     * Award object.
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The authenticated trooper.
     * @return Award The new Award instance.
     */
    private function getAward(Request $request, Trooper $trooper): Award
    {
        $award = new Award();

        if ($request->has('copy_id'))
        {
            $copy_id = $request->query('copy_id');

            $copy = Award::moderatedBy($trooper)->findOrFail($copy_id);

            $award->organization_id = $copy->organization_id;
            $award->name = 'Copy of ' . $copy->name;
            $award->frequency = $copy->frequency;
        }

        return $award;
    }

    /**
     * Assigns an organization to the award if an 'organization_id' is provided.
     *
     * This method ensures that if the user is not an administrator, they can only
     * assign an organization that they are authorized to moderate. It will throw
     * a ModelNotFoundException if a moderator attempts to assign an un-moderated
     * organization.
     *
     * @param Request $request The incoming HTTP request.
     * @param Award $award The award being created.
     * @param Trooper $trooper The authenticated trooper.
     * @return void
     */
    private function assignOrganization(Request $request, Award $award, Trooper $trooper)
    {
        if ($request->has('organization_id'))
        {
            $award->organization_id = $request->query('organization_id');
        }

        if ($award->organization_id != null)
        {
            $q = Organization::moderatedBy($trooper);

            $award->organization = $q->findOrFail($award->organization_id);
        }
    }
}
