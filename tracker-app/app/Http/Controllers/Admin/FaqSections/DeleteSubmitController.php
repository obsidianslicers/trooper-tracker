<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\Controller;
use App\Messages\Faq\Commands\Sections\DeleteFaqSection;
use App\Models\FaqSection;
use Hyperdrive\CommsHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteSubmitController extends Controller
{
    public function __invoke(Request $request, FaqSection $section): RedirectResponse
    {
        $faq_count = $section->faqs()->count();

        if ($faq_count > 0)
        {
            return redirect()
                ->route('admin.faq.sections.list')
                ->with('danger', "Cannot delete \"{$section->label}\" — it has {$faq_count} FAQ item(s). Move or delete them first.");
        }

        $message = CommsHelper::deleted($section);

        DeleteFaqSection::call(section: $section);

        return redirect()
            ->route('admin.faq.sections.list')
            ->with('success', $message);
    }
}
