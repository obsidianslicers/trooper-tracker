<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Organizations\CreateRequest;
use App\Messages\Organizations\Commands\CreateSubOrganization;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class CreateSubmitController extends Controller
{
    public function __invoke(CreateRequest $request, Organization $parent): RedirectResponse
    {
        $organization = CreateSubOrganization::call(
            parent: $parent,
            name: $request->validated('name'),
        );

        FlashType::created($organization);

        return redirect()->route('admin.organizations.update', compact('organization'));
    }
}
