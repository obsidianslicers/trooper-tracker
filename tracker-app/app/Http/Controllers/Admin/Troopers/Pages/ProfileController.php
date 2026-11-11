<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\PageData\ProfilePageData;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Displays the command staff trooper profile page.
 *
 * A single page carries every tab (profile, authority, memberships, guardian,
 * costumes, events and changes); each tab posts back to its own command controller.
 */
class ProfileController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * @param  Request  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper being managed
     */
    public function __invoke(Request $request, Trooper $trooper): InertiaResponse|SymfonyResponse
    {
        $this->authorize('update', $trooper);

        $data = ProfilePageData::call($request);

        return Inertia::render('admin/troopers/Profile', $data);
    }
}
