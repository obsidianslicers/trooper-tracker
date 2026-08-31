<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Controller;
use App\Messages\Faq\Commands\ReorderFaqSections;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Http\Request;

class ReorderSectionsSubmitController extends Controller
{
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $ordered_ids = $request->input('ids', []);

        ReorderFaqSections::call(ordered_ids: $ordered_ids);

        return redirect()->back();
    }
}
