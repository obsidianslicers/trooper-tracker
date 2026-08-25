<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\Controller;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreateController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('FAQ', 'admin.faq.list');
        $this->crumbs->addRoute('Sections', 'admin.faq.sections.list');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        return Inertia::render('admin/faq/sections/Create');
    }
}
