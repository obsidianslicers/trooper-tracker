<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ListController extends MagicBusController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $section_id = $request->query('section_id') ? (int) $request->query('section_id') : null;

        $query = Faq::query()->with('faq_section')->orderBy(Faq::SORT_ORDER)->orderBy(Faq::ID);

        if ($section_id)
        {
            $query->where(Faq::SECTION_ID, $section_id);
        }

        $sortable = $section_id !== null;
        $items = $sortable ? $query->get() : $query->paginate(20)->withQueryString();

        return Inertia::render('admin/faq/List', [
            'items' => $items,
            'sections' => FaqSection::orderBy(FaqSection::SORT_ORDER)->get(),
            'section_id' => $section_id,
            'sortable' => $sortable,
        ]);
    }
}
