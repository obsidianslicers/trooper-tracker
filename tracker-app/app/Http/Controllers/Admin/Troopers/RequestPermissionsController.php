<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\PageData\Permissions\RequestPermissionsPageData;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays the page where moderators request role or forum group changes for a trooper.
 */
class RequestPermissionsController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        $data = RequestPermissionsPageData::call($request);

        return Inertia::render('admin/troopers/RequestPermissions', $data);
    }
}
