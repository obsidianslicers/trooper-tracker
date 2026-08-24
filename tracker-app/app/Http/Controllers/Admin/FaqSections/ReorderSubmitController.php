<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\FaqSections;

use App\Features\Faq\Commands\ReorderFaqSectionsCommand;
use App\Http\Controllers\MagicBusController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReorderSubmitController extends MagicBusController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $ordered_ids = $request->input('ids', []);

        $this->bus->send(new ReorderFaqSectionsCommand($ordered_ids));

        return redirect()->back();
    }
}
