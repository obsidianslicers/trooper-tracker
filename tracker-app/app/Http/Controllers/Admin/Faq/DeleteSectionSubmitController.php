<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faq\DeleteSectionRequest;
use App\Messages\Faq\Commands\DeleteFaqSection;
use App\Models\FaqSection;
use Hyperdrive\CommsHelper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DeleteSectionSubmitController extends Controller
{
    public function __invoke(DeleteSectionRequest $request, FaqSection $section): InertiaResponse|SymfonyResponse
    {
        $faq_count = $section->faqs()->count();

        if ($faq_count > 0)
        {
            FlashType::danger("Cannot delete \"{$section->label}\" — it has {$faq_count} FAQ item(s). Move or delete them first.");

            return Inertia::render('admin/faq/ListSections');
        }

        $message = CommsHelper::deleted($section);

        DeleteFaqSection::call(section: $section);

        FlashType::success($message);

        return Inertia::render('admin/faq/ListSections');
    }
}
