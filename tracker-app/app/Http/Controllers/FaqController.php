<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FaqSection;
use App\Models\Faq;
use Illuminate\Contracts\View\View;

class FaqController extends MagicBusController
{
    public function __invoke(): View
    {
        $items = Faq::query()
            ->orderBy(Faq::SORT_ORDER)
            ->orderBy(Faq::ID)
            ->get()
            ->groupBy(fn (Faq $faq) => $faq->section->value);

        $sections = FaqSection::cases();

        return view('pages.faq', compact('items', 'sections'));
    }
}
