<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Http\Controllers\MagicBusController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CreateController extends MagicBusController
{
    public function __invoke(Request $request): InertiaResponse
    {
        return Inertia::render('admin/faq/sections/Create');
    }
}
