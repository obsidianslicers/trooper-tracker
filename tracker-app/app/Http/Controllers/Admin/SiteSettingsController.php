<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\MagicBusController;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteSettingsController extends MagicBusController
{
    public function show(): View
    {
        $site_closed = (bool) SiteSetting::get('site_closed', false);
        $site_message = (string) SiteSetting::get('site_message', '');

        return view('pages.admin.settings', compact('site_closed', 'site_message'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'site_message' => ['nullable', 'string', 'max:500'],
        ]);

        SiteSetting::set('site_closed', $request->boolean('site_closed') ? '1' : '0');
        SiteSetting::set('site_message', $request->input('site_message', ''));

        $this->flash->success('Site settings updated.');

        return redirect()->route('admin.settings');
    }
}
