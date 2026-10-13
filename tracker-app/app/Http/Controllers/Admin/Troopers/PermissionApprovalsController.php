<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\PageData\Permissions\PermissionApprovalsPageData;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays pending permission requests for administrators to approve or deny.
 */
class PermissionApprovalsController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        $data = PermissionApprovalsPageData::call($request);

        return Inertia::render('admin/troopers/PermissionApprovals', $data);
    }
}
