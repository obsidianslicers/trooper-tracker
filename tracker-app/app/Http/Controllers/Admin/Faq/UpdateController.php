<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UpdateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('FAQ', 'admin.faq.list');
    }

    public function __invoke(Request $request, Faq $faq): View
    {
        return view('pages.admin.faq.update', [
            'faq' => $faq,
            'sections' => FaqSection::orderBy(FaqSection::SORT_ORDER)->pluck(FaqSection::LABEL, FaqSection::ID),
        ]);
    }
}
