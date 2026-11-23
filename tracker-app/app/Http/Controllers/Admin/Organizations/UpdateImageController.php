<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\Organizations\Commands\UpdateOrganizationLogo;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateImageController extends Controller
{
    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        UpdateOrganizationLogo::call(organization: $organization, logo: $request->file('logo'));

        FlashType::SUCCESS->flash(__('admin.Logo updated.'));

        return redirect()->route('admin.organizations.update', compact('organization'));
    }
}
