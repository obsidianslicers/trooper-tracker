<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Features\Faq\Commands\CreateFaqCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Faq\CreateRequest;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends MagicBusController
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $faq = $this->bus->send(new CreateFaqCommand(
            section_id: (int) $request->validated('section_id'),
            title: $request->validated('title'),
            description: $request->validated('description'),
            video_url: $request->validated('video_url'),
        ));

        $this->flash->created($faq);

        return redirect()->route('admin.faq.update', compact('faq'));
    }
}
