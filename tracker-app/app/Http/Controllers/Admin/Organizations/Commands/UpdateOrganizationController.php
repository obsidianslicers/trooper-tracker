<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Organizations\UpdateRequest;
use App\Messages\Organizations\Commands\UpdateOrganization;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class UpdateOrganizationController extends Controller
{
    public function __invoke(UpdateRequest $request, Organization $organization): RedirectResponse
    {
        $organization = UpdateOrganization::call(
            organization: $organization,
            name: $request->validated('name'),
            validated: $request->validated(),
            sets_synchronizer: $request->canChooseSynchronizer(),
            synchronizer: $request->validated('synchronizer'),
        );

        FlashType::updated($organization);

        return redirect()->route('admin.organizations.index');
    }
}
