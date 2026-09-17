<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\UpdateSectionRequest;
use App\Messages\Faq\Commands\UpdateFaqSection;
use App\Models\FaqSection;
use Hyperdrive\CommsHelper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UpdateSectionSubmitController extends Controller
{
    public function __invoke(UpdateSectionRequest $request, FaqSection $section): InertiaResponse|SymfonyResponse
    {
        $section = UpdateFaqSection::call($request);

        $url = route('admin.faq.index');

        FlashType::success(CommsHelper::updated($section));

        return Inertia::location($url);
    }
}
