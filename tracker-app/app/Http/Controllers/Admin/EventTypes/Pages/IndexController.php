<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\EventTypes\Pages;

use App\Http\Controllers\PageController;
use App\Messages\EventTypes\PageData\IndexPageData;
use App\Models\EventType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Lists the event types in the order the event form offers them.
 */
class IndexController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $this->authorize('viewAny', EventType::class);

        $data = IndexPageData::call();

        return Inertia::render('admin/event-types/Index', $data);
    }
}
