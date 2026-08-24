<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use App\Models\FaqSection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UpdateController extends MagicBusController
{
    public function __invoke(Request $request, FaqSection $section): InertiaResponse
    {
        return Inertia::render('admin/faq/sections/Update', [
            'section' => $section->load(['created_by', 'updated_by']),
        ]);
    }
}
