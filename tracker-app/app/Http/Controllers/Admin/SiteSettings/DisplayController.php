<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SiteSettings;

use App\Http\Controllers\MagicBusController;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DisplayController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    public function __invoke(Request $request): View
    {
        $site_closed = (bool) SiteSetting::getValue('site_closed', false);
        $site_message = (string) SiteSetting::getValue('site_message', '');

        return view('pages.admin.settings', compact('site_closed', 'site_message'));
    }
}
