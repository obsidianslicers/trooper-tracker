<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\Controller;
use App\Messages\Faq\Commands\Sections\ReorderFaqSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReorderSubmitController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $ordered_ids = $request->input('ids', []);

        ReorderFaqSections::call(ordered_ids: $ordered_ids);

        return redirect()->back();
    }
}
