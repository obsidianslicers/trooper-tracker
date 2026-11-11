<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\EventTypes\Pages;

use App\Http\Controllers\PageController;
use App\Messages\EventTypes\PageData\UpdatePageData;
use App\Models\EventType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UpdateController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Event Types', 'admin.event-types.index');
    }

    public function __invoke(Request $request, EventType $event_type): InertiaResponse|SymfonyResponse
    {
        $this->authorize('update', $event_type);

        $data = UpdatePageData::call(event_type: $event_type);

        return Inertia::render('admin/event-types/Update', $data);
    }
}
