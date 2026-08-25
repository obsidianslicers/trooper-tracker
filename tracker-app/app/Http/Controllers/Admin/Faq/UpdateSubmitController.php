<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\UpdateRequest;
use App\Messages\Faq\Commands\UpdateFaqItem;
use App\Models\Faq;
use Hyperdrive\CommsHelper;
use Illuminate\Http\RedirectResponse;

class UpdateSubmitController extends Controller
{
    public function __invoke(UpdateRequest $request, Faq $faq): RedirectResponse
    {
        $faq = UpdateFaqItem::call($request);

        return redirect()
            ->route('admin.faq.update', compact('faq'))
            ->with('warning', CommsHelper::updated($faq));
    }
}
