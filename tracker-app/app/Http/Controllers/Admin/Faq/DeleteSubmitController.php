<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Messages\Faq\Commands\DeleteFaqItem;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteSubmitController extends Controller
{
    public function __invoke(Request $request, Faq $faq): RedirectResponse
    {
        $title = $faq->title;

        DeleteFaqItem::call(faq: $faq);

        return redirect()
            ->route('admin.faq.list')
            ->with('success', "Deleted FAQ item \"{$title}\"");
    }
}
