<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\Commands\StoreFaqItemRequest;
use App\Messages\Faq\Commands\CreateFaqItem;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StoreFaqItemController extends Controller
{
    public function __invoke(StoreFaqItemRequest $request): InertiaResponse|SymfonyResponse
    {
        $item = CreateFaqItem::call($request);

        $url = route('admin.faq.items.edit', compact('item'));

        FlashType::created($item);

        return Inertia::location($url);
    }
}
