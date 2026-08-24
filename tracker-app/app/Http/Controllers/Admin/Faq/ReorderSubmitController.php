<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faq;

use App\Features\Faq\Commands\ReorderFaqItemsCommand;
use App\Http\Controllers\MagicBusController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReorderSubmitController extends MagicBusController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $ordered_ids = $request->input('ids', []);

        $this->bus->send(new ReorderFaqItemsCommand($ordered_ids));

        return redirect()->back();
    }
}
