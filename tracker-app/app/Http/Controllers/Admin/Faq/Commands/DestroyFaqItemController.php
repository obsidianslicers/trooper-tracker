<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\Commands\DestroyFaqItemRequest;
use App\Messages\Faq\Commands\DeleteFaqItem;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DestroyFaqItemController extends Controller
{
    public function __invoke(DestroyFaqItemRequest $request, Faq $item): InertiaResponse|SymfonyResponse
    {
        DeleteFaqItem::call(faq: $item);

        FlashType::deleted($item);

        return Inertia::render('admin/faq/Index');
    }
}
