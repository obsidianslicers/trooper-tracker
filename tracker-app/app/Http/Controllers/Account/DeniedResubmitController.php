<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\ResubmitDeniedTrooperCommand;
use App\Features\Troopers\Exceptions\DuplicateOrganizationIdentifierException;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ResubmitDeniedRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class DeniedResubmitController extends MagicBusController
{
    public function __invoke(ResubmitDeniedRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        try
        {
            $this->bus->send(new ResubmitDeniedTrooperCommand(
                $trooper,
                $request->validated('organizations', []),
            ));
        }
        catch (DuplicateOrganizationIdentifierException $exception)
        {
            throw ValidationException::withMessages([
                'organizations' => $exception->flashMessage(),
            ]);
        }

        $this->flash->success('Your application has been resubmitted. You will receive an email when a decision is made.');

        return redirect()->route('account.pending');
    }
}
