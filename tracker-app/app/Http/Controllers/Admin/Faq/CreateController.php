<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FaqSection;
use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
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

        if ($section = FaqSection::tryFrom((string) $request->query('section')))
        {
            $faq->section = $section;
        }

        return view('pages.admin.faq.create', [
            'faq'      => $faq,
            'sections' => FaqSection::toOptions(),
        ]);
    }
}
