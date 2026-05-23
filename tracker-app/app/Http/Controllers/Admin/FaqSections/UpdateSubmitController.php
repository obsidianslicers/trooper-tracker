<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\FaqSections\UpdateRequest;
use App\Models\FaqSection;
use Illuminate\Http\RedirectResponse;

class UpdateSubmitController extends MagicBusController
{
    public function __invoke(UpdateRequest $request, FaqSection $section): RedirectResponse
    {
        $section->label = $request->validated('label');
        $section->icon = $request->validated('icon');

        $section->save();

        $this->flash->updated($section);

        return redirect()->route('admin.faq.sections.update', compact('section'));
    }
}
