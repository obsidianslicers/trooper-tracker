<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use App\Models\FaqSection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CreateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('FAQ', 'admin.faq.list');
        $this->crumbs->addRoute('Sections', 'admin.faq.sections.list');
    }

    public function __invoke(Request $request): View
    {
        $section = new FaqSection;
        $section->sort_order = 0;

        return view('pages.admin.faq.sections.create', compact('section'));
    }
}
