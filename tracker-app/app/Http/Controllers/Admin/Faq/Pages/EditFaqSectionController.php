<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Faq\PageData\UpdateSectionPageData;
use App\Models\FaqSection;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EditFaqSectionController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.FAQ'), 'admin.faq.index');
    }

    public function __invoke(Request $request, FaqSection $section): InertiaResponse|SymfonyResponse
    {
        $data = UpdateSectionPageData::call($request);

        return Inertia::render('admin/faq/UpdateSection', $data);
    }
}
