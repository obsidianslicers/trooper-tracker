<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\CreateRequest;
use App\Messages\Faq\Commands\CreateFaqItem;
use Hyperdrive\CommsHelper;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends Controller
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $faq = CreateFaqItem::call($request);

        return redirect()
            ->route('admin.faq.update', compact('faq'))
            ->with('success', CommsHelper::created($faq));
    }
}
