<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\CreateItemRequest;
use App\Messages\Faq\Commands\CreateFaqItem;
use Hyperdrive\CommsHelper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CreateItemSubmitController extends Controller
{
    public function __invoke(CreateItemRequest $request): InertiaResponse|SymfonyResponse
    {
        $item = CreateFaqItem::call($request);

        $url = route('admin.faq.items.update', compact('item'));

        FlashType::success(CommsHelper::created($item));

        return Inertia::location($url);
    }
}
