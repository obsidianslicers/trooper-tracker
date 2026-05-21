<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteSubmitController extends MagicBusController
{
    public function __invoke(Request $request, Faq $faq): RedirectResponse
    {
        $title = $faq->title;

        $faq->delete();

        $this->flash->success("Deleted FAQ item \"{$title}\"");

        return redirect()->route('admin.faq.list');
    }
}
