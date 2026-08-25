<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\Controller;
use App\Messages\Faq\PageData\Sections\UpdateFaqSectionPageData;
use App\Models\FaqSection;
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
        $this->crumbs->addRoute('Sections', 'admin.faq.sections.list');
    }

    public function __invoke(Request $request, FaqSection $section): InertiaResponse
    {
        $data = UpdateFaqSectionPageData::call($request);

        return Inertia::render('admin/faq/sections/Update', $data);
    }
}
