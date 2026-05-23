<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ListController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(Request $request): View
    {
        $section_id = $request->query('section_id') ? (int) $request->query('section_id') : null;

        $query = Faq::query()->with('section')->orderBy(Faq::SORT_ORDER)->orderBy(Faq::ID);

        if ($section_id)
        {
            $query->where(Faq::SECTION_ID, $section_id);
        }

        $sortable = $section_id !== null;
        $items = $sortable ? $query->get() : $query->paginate(20)->withQueryString();

        return view('pages.admin.faq.list', [
            'items' => $items,
            'sections' => FaqSection::orderBy(FaqSection::SORT_ORDER)->get(),
            'section_id' => $section_id,
            'sortable' => $sortable,
        ]);
    }
}
