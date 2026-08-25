<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Messages\Faq\Commands\ReorderFaqItems;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReorderSubmitController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $ordered_ids = $request->input('ids', []);

        ReorderFaqItems::call(ordered_ids: $ordered_ids);

        return redirect()->back();
    }
}
