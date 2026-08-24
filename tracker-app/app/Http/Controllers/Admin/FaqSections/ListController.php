<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ListController extends MagicBusController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $sections = FaqSection::withCount('faqs')
            ->orderBy(FaqSection::SORT_ORDER)
            ->orderBy(FaqSection::ID)
            ->get();

        return Inertia::render('admin/faq/sections/List', compact('sections'));
    }
}
