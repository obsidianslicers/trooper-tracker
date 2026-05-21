<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CreateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('FAQ', 'admin.faq.list');
    }

    public function __invoke(Request $request): View
    {
        $faq = new Faq;
        $faq->sort_order = 0;
        $faq->section_id = $request->query('section_id') ? (int) $request->query('section_id') : null;

        return view('pages.admin.faq.create', [
            'faq' => $faq,
            'sections' => FaqSection::orderBy(FaqSection::SORT_ORDER)->pluck(FaqSection::LABEL, FaqSection::ID),
        ]);
    }
}
