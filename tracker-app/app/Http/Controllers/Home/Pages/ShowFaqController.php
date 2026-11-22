<?php

declare(strict_types=1);

namespace App\Http\Controllers\Home\Pages;

use App\Http\Controllers\PageController;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Contracts\View\View;

class ShowFaqController extends PageController
{
    public function __invoke(): View
    {
        $sections = FaqSection::orderBy(FaqSection::SORT_ORDER)->get();

        $items = Faq::query()
            ->orderBy(Faq::SORT_ORDER)
            ->orderBy(Faq::ID)
            ->get()
            ->groupBy(Faq::SECTION_ID);

        return view('pages.faq', compact('items', 'sections'));
    }
}
