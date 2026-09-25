<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\ServiceRecords;

use App\Http\Controllers\Controller;
use App\Messages\ServiceRecords\PageData\MissingCreditsPageData;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MissingCreditsController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Missing Credit', 'admin.service-records.missing-credits');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        $data = MissingCreditsPageData::call($request);

        return Inertia::render('admin/service-records/MissingCredits', $data);
    }
}
