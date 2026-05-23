<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\FaqSections\CreateRequest;
use App\Models\FaqSection;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends MagicBusController
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $section = new FaqSection;

        $section->label = $request->validated('label');
        $section->icon = $request->validated('icon');
        $section->sort_order = $request->validated('sort_order') ?? 0;

        $section->save();

        $this->flash->created($section);

        return redirect()->route('admin.faq.sections.update', compact('section'));
    }
}
