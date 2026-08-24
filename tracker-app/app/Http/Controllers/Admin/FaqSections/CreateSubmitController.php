<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Features\Faq\Commands\CreateFaqSectionCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\FaqSections\CreateRequest;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends MagicBusController
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $section = $this->bus->send(new CreateFaqSectionCommand(
            label: $request->validated('label'),
            icon: $request->validated('icon'),
        ));

        $this->flash->created($section);

        return redirect()->route('admin.faq.sections.update', compact('section'));
    }
}
