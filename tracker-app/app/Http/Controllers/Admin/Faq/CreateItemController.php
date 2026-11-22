<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Messages\Faq\PageData\CreateItemPageData;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CreateItemController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.FAQ'), 'admin.faq.index');
    }

    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $data = CreateItemPageData::call($request);

        return Inertia::render('admin/faq/CreateItem', $data);
    }
}
