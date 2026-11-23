<?php

declare(strict_types=1);

namespace App\Http\Controllers\Home\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Faq\PageData\FaqPageData;
use Illuminate\Contracts\View\View;

class ShowFaqController extends PageController
{
    public function __invoke(): View
    {
        $data = FaqPageData::call();

        return view('pages.faq', $data);
    }
}
