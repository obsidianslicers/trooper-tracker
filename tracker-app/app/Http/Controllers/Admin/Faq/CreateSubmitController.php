<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Faq\CreateRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends MagicBusController
{
    public function __invoke(CreateRequest $request): RedirectResponse
    {
        $faq = new Faq;

        $faq->section_id = $request->validated('section_id');
        $faq->title = $request->validated('title');
        $faq->description = $request->validated('description');
        $faq->video_url = $request->validated('video_url');
        $faq->sort_order = $request->validated('sort_order') ?? 0;

        $faq->save();

        $this->flash->created($faq);

        return redirect()->route('admin.faq.update', compact('faq'));
    }
}
