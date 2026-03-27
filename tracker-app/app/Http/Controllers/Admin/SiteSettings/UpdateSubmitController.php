<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SiteSettings;

use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\SiteSettings\UpdateRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;

class UpdateSubmitController extends MagicBusController
{
    public function __invoke(UpdateRequest $request): RedirectResponse
    {
        SiteSetting::setValue('site_closed', $request->boolean('site_closed') ? '1' : '0');
        SiteSetting::setValue('site_message', $request->validated('site_message', ''));

        $this->flash->success('Site settings updated.');

        return redirect()->route('admin.settings');
    }
}
