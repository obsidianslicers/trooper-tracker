<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Messages\Faq\PageData\Sections\ListFaqSectionsPageData;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ListSectionsController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('FAQ', 'admin.faq.list');
        $this->crumbs->addRoute('Sections', 'admin.faq.sections.list');
    }

    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $data = ListFaqSectionsPageData::call($request);

        return Inertia::render('admin/faq/ListSections', $data);
    }
}
