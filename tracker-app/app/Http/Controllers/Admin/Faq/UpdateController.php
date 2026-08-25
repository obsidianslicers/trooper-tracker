<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Messages\Faq\PageData\UpdateFaqPageData;
use App\Models\Faq;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UpdateController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('FAQ', 'admin.faq.list');
    }

    public function __invoke(Request $request, Faq $faq): InertiaResponse
    {
        $data = UpdateFaqPageData::call($request);

        return Inertia::render('admin/faq/Update', $data);
    }
}
