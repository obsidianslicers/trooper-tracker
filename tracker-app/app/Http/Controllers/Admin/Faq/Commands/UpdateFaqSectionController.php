<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\Commands\UpdateFaqSectionRequest;
use App\Messages\Faq\Commands\UpdateFaqSection;
use App\Models\FaqSection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UpdateFaqSectionController extends Controller
{
    public function __invoke(UpdateFaqSectionRequest $request, FaqSection $section): InertiaResponse|SymfonyResponse
    {
        $section = UpdateFaqSection::call($request);

        $url = route('admin.faq.index');

        FlashType::updated($section);

        return Inertia::location($url);
    }
}
