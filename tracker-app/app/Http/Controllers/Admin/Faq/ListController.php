<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FaqSection;
use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
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
        $section = $request->query('section');

        $query = Faq::query()->orderBy(Faq::SORT_ORDER)->orderBy(Faq::ID);

        if ($section && FaqSection::tryFrom($section))
        {
            $query->where(Faq::SECTION, $section);
        }

        $items = $query->paginate(20)->withQueryString();

        return view('pages.admin.faq.list', [
            'items'    => $items,
            'sections' => FaqSection::cases(),
            'section'  => $section,
        ]);
    }
}
