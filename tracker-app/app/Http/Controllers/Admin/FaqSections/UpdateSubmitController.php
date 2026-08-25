<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqSections\UpdateRequest;
use App\Messages\Faq\Commands\Sections\UpdateFaqSection;
use App\Models\FaqSection;
use Hyperdrive\CommsHelper;
use Illuminate\Http\RedirectResponse;

class UpdateSubmitController extends Controller
{
    public function __invoke(UpdateRequest $request, FaqSection $section): RedirectResponse
    {
        $section = UpdateFaqSection::call($request);

        return redirect()
            ->route('admin.faq.sections.update', compact('section'))
            ->with('success', CommsHelper::updated($section));
    }
}
