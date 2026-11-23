<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\Commands\UpdateFaqItemRequest;
use App\Messages\Faq\Commands\UpdateFaqItem;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UpdateFaqItemController extends Controller
{
    public function __invoke(UpdateFaqItemRequest $request, Faq $item): InertiaResponse|SymfonyResponse
    {
        $item = UpdateFaqItem::call($request);

        $url = route('admin.faq.index');

        FlashType::warning($item);

        return Inertia::location($url);
    }
}
