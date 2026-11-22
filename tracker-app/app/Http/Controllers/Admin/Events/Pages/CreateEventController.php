<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\Admin\CreateEventPageData;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows Create Event: paste a request message to fill the form in, or fill it in by hand.
 */
class CreateEventController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.dashboard');
        $this->crumbs->addRoute(__('admin.Events'), 'admin.events.index');
    }

    public function __invoke(Request $request): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('admin/events/Create', CreateEventPageData::call($request));
    }
}
