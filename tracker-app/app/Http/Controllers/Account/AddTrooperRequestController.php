<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Messages\Troopers\Commands\TrooperMemberships\CreateTrooperRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddTrooperOrganizationRequest;
use App\Messages\Troopers\Queries\TrooperMembership\GetTrooperRequests;
use App\Messages\Account\Resources\TrooperRequestCollection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Submits a club join request for the authenticated trooper via HTMX.
 *
 * Returns an updated row partial showing the pending state.
 */
class AddTrooperRequestController extends Controller
{
    /**
     * Summary of __invoke
     * @param AddTrooperOrganizationRequest $request
     * @return void
     */
    public function __invoke(AddTrooperOrganizationRequest $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        $identifier = $request->filled('identifier') ? $request->string('identifier')->toString() : null;

        CreateTrooperRequest::call(
            trooper: $trooper,
            organization: $request->integer('organization_id'),
            identifier: $identifier,
        );

        $collection = GetTrooperRequests::call(trooper: $trooper);

        $data = [
            'results' => [
                'organization_requests' => new TrooperRequestCollection($collection)
            ]
        ];

        return Inertia::render('account/Index', $data);
    }
}
