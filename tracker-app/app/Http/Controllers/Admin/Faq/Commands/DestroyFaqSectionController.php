<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\Commands\DestroyFaqSectionRequest;
use App\Messages\Faq\Commands\DeleteFaqSection;
use App\Models\FaqSection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DestroyFaqSectionController extends Controller
{
    public function __invoke(DestroyFaqSectionRequest $request, FaqSection $section): InertiaResponse|SymfonyResponse
    {
        $faq_count = $section->faqs()->count();

        if ($faq_count > 0)
        {
            FlashType::danger(trans_choice(
                'admin.Cannot delete ":section" — it has :count FAQ item. Move or delete it first.|Cannot delete ":section" — it has :count FAQ items. Move or delete them first.',
                $faq_count,
                ['section' => $section->label]
            ));

            return Inertia::render('admin/faq/Index');
        }

        DeleteFaqSection::call(section: $section);

        FlashType::deleted($section);

        return Inertia::render('admin/faq/Index');
    }
}
