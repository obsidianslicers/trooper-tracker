<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\UpdateItemRequest;
use App\Messages\Faq\Commands\UpdateFaqItem;
use App\Models\Faq;
use Hyperdrive\CommsHelper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UpdateItemSubmitController extends Controller
{
    public function __invoke(UpdateItemRequest $request, Faq $faq): InertiaResponse|SymfonyResponse
    {
        $faq = UpdateFaqItem::call($request);

        $url = route('admin.faq.update', compact('faq'));

        FlashType::warning(CommsHelper::updated($faq));

        return Inertia::location($url);
    }
}
