<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\CreateSectionRequest;
use App\Messages\Faq\Commands\CreateFaqSection;
use Hyperdrive\CommsHelper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CreateSectionSubmitController extends Controller
{
    public function __invoke(CreateSectionRequest $request): InertiaResponse|SymfonyResponse
    {
        $section = CreateFaqSection::call($request);

        $url = route('admin.faq.section.update', compact('section'));

        FlashType::success(CommsHelper::created($section));

        return Inertia::location($url);
    }
}
