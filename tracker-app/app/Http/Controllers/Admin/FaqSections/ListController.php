<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use App\Models\FaqSection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ListController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('FAQ', 'admin.faq.list');
    }

    public function __invoke(Request $request): View
    {
        $sections = FaqSection::withCount('faqs')
            ->orderBy(FaqSection::SORT_ORDER)
            ->orderBy(FaqSection::ID)
            ->get();

        return view('pages.admin.faq.sections.list', compact('sections'));
    }
}
