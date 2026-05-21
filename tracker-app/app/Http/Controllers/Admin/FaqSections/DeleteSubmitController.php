<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use App\Models\FaqSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteSubmitController extends MagicBusController
{
    public function __invoke(Request $request, FaqSection $section): RedirectResponse
    {
        $faq_count = $section->faqs()->count();

        if ($faq_count > 0)
        {
            $this->flash->error("Cannot delete \"{$section->label}\" — it has {$faq_count} FAQ item(s). Move or delete them first.");

            return redirect()->route('admin.faq.sections.list');
        }

        $label = $section->label;

        $section->delete();

        $this->flash->success("Deleted section \"{$label}\"");

        return redirect()->route('admin.faq.sections.list');
    }
}
