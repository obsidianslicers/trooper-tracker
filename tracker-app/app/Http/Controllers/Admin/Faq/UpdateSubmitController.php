<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Features\Faq\Commands\UpdateFaqCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Faq\UpdateRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;

class UpdateSubmitController extends MagicBusController
{
    public function __invoke(UpdateRequest $request, Faq $faq): RedirectResponse
    {
        $faq = $this->bus->send(new UpdateFaqCommand(
            faq: $faq,
            section_id: (int) $request->validated('section_id'),
            title: $request->validated('title'),
            description: $request->validated('description'),
            video_url: $request->validated('video_url'),
        ));

        $this->flash->updated($faq);

        return redirect()->route('admin.faq.update', compact('faq'));
    }
}
