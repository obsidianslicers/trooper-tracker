<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\Admin\CopyEventPageData;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Copy Event form: a new name and start for a copy of an event, its shifts and clubs.
 */
class CopyController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('admin.Command Staff'), 'admin.display');
        $this->crumbs->addRoute(__('admin.Events'), 'admin.events.list');
    }

    public function __invoke(Event $event): Response
    {
        $this->authorize('copy', $event);

        return Inertia::render('admin/events/Copy', CopyEventPageData::call(event: $event));
    }
}
