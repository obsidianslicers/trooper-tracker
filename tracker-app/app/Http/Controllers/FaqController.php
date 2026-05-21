<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class FaqController extends MagicBusController
{
    public function __invoke(): View
    {
        return view('pages.faq');
    }
}
