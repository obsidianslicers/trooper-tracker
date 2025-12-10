<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Notices;

use App\Enums\NoticeType;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class CreateController
 *
 * Handles displaying the form to create a new notice.
 * @package App\Http\Controllers\Admin\Notices
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
        $this->crumbs->addRoute('Notices', 'admin.notices.list');
    }

    /**
     * Handle the request to display the notice creation page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view containing
     * the form to create a new notice. If a `copy_id` is provided, it pre-populates
     * the form with data from an existing notice. If an `organization_id` is
     * provided, it pre-selects that organization. It ensures non-administrators
     * can only interact with notices and organizations they are authorized to moderate.
     *
     * @param Request $request The incoming HTTP request object.
     * @return View The rendered notice creation view.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('create', Notice::class);

        $trooper = $request->user();

        $notice = $this->getNotice($request, $trooper);

        $this->assignOrganization($request, $notice, $trooper);

        $data = [
            'notice' => $notice,
        ];

        return view('pages.admin.notices.create', $data);
    }

    /**
     * Creates a new Notice instance, optionally copying data from an existing notice.
     *
     * If a 'copy_id' is present in the request, it finds the corresponding notice
     * (ensuring the user has moderation rights) and copies its properties to a new
     * Notice object.
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The authenticated trooper.
     * @return Notice The new Notice instance.
     */
    private function getNotice(Request $request, Trooper $trooper): Notice
    {
        $notice = new Notice();
        $notice->type = NoticeType::INFO;

        if ($request->has('copy_id'))
        {
            $copy_id = $request->query('copy_id');

            $copy = Notice::moderatedBy($trooper)->findOrFail($copy_id);

            $notice->organization_id = $copy->organization_id;
            $notice->type = $copy->type;
            $notice->title = 'Copy of ' . $copy->title;
            $notice->starts_at = $copy->starts_at;
            $notice->ends_at = $copy->ends_at;
            $notice->message = $copy->message;
        }

        return $notice;
    }

    /**
     * Assigns an organization to the notice if an 'organization_id' is provided.
     *
     * This method ensures that if the user is not an administrator, they can only
     * assign an organization that they are authorized to moderate. It will throw
     * a ModelNotFoundException if a moderator attempts to assign an un-moderated
     * organization.
     *
     * @param Request $request The incoming HTTP request.
     * @param Notice $notice The notice being created.
     * @param Trooper $trooper The authenticated trooper.
     * @return void
     */
    private function assignOrganization(Request $request, Notice $notice, Trooper $trooper)
    {
        if ($request->has('organization_id'))
        {
            $notice->organization_id = $request->query('organization_id');
        }

        if ($notice->organization_id != null)
        {
            $q = Organization::moderatedBy($trooper);

            $notice->organization = $q->findOrFail($notice->organization_id);
        }
    }
}
