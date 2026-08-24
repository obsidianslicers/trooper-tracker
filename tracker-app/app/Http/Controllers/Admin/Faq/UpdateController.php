<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UpdateController extends MagicBusController
{
    public function __invoke(Request $request, Faq $faq): InertiaResponse
    {
        return Inertia::render('admin/faq/Update', [
            'faq' => $faq->load(['created_by', 'updated_by']),
            'sections' => $this->sectionOptions(),
        ]);
    }

    private function sectionOptions(): array
    {
        return FaqSection::orderBy(FaqSection::SORT_ORDER)
            ->get()
            ->map(fn (FaqSection $section) => ['value' => $section->id, 'label' => $section->label])
            ->all();
    }
}
