<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\MagicBusController;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreateController extends MagicBusController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $section_id = $request->query('section_id') ? (int) $request->query('section_id') : null;

        return Inertia::render('admin/faq/Create', [
            'section_id' => $section_id,
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
