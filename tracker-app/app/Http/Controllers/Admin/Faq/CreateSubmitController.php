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

        $section_id = $request->validated('section_id');
        $max_order = Faq::where(Faq::SECTION_ID, $section_id)->max(Faq::SORT_ORDER) ?? 0;

        $faq->section_id = $section_id;
        $faq->title = $request->validated('title');
        $faq->description = $request->validated('description');
        $faq->video_url = $request->validated('video_url');
        $faq->sort_order = $max_order + 1;

        $faq->save();

        $this->flash->created($faq);

        return redirect()->route('admin.faq.update', compact('faq'));
    }
}
