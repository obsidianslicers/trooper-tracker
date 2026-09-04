<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Faq\Commands\DeleteFaqItem;
use App\Models\Faq;
use Hyperdrive\CommsHelper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Http\Request;

class DeleteItemSubmitController extends Controller
{
    public function __invoke(Request $request, Faq $faq): InertiaResponse|SymfonyResponse
    {
        $message = CommsHelper::deleted($faq);

        DeleteFaqItem::call(faq: $faq);

        $url = route('admin.faq.index');

        FlashType::success($message);

        return Inertia::location($url);
    }
}
