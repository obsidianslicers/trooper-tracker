<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Features\Faq\Commands\UpdateFaqSectionCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\FaqSections\UpdateRequest;
use App\Models\FaqSection;
use Illuminate\Http\RedirectResponse;

class UpdateSubmitController extends MagicBusController
{
    public function __invoke(UpdateRequest $request, FaqSection $section): RedirectResponse
    {
        $section = $this->bus->send(new UpdateFaqSectionCommand(
            section: $section,
            label: $request->validated('label'),
            icon: $request->validated('icon'),
        ));

        $this->flash->updated($section);

        return redirect()->route('admin.faq.sections.update', compact('section'));
    }
}
