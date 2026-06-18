<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\ResubmitDeniedTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeniedResubmitController extends MagicBusController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $trooper = $request->user();

        abort_unless($trooper->is_denied, 409);

        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', Rule::exists('tt_organizations', 'id')],
            'identifier'      => ['nullable', 'string', 'max:64'],
        ]);

        $organization = isset($validated['organization_id'])
            ? Organization::find($validated['organization_id'])
            : null;

        $this->bus->send(new ResubmitDeniedTrooperCommand(
            $trooper,
            $organization,
            $validated['identifier'] ?? null,
        ));

        $this->flash->success('Your application has been resubmitted. You will receive an email when a decision is made.');

        return redirect()->route('account.denied');
    }
}
