<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqSections\CreateRequest;
use App\Messages\Faq\Commands\Sections\CreateFaqSection;
use Hyperdrive\CommsHelper;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends Controller
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $section = CreateFaqSection::call($request);

        return redirect()
            ->route('admin.faq.sections.update', compact('section'))
            ->with('success', CommsHelper::created($section));
    }
}
