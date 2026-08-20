<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Messages\Account\Resources\TrooperCostumeCollection;
use App\Messages\Troopers\Commands\AddCostumeToTrooper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddCostumeRequest;
use App\Messages\Troopers\Queries\GetTrooperCostumes;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for adding a costume to the authenticated trooper's profile.
 *
 * This controller validates the request, dispatches the necessary command to add the costume,
 * and redirects back to the update profile page.
 */
class AddCostumeController extends Controller
{
    /**
     * Handle the incoming request to add a costume to the trooper's profile.
     *
     * @param  AddCostumeRequest  $request  The validated request to add a costume
     * @return  InertiaResponse|SymfonyResponse Redirect to the update profile page with success message
     */
    public function __invoke(AddCostumeRequest $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        AddCostumeToTrooper::call(
            trooper: $trooper,
            costume_id: $request->validated('costume_id'),
            organization_ids: $request->validated('organization_ids')
        );

        $trooper_costumes = GetTrooperCostumes::call(trooper: $trooper);

        $data = [
            'results' => [
                'trooper_costumes' => new TrooperCostumeCollection($trooper_costumes),
            ],
        ];

        return Inertia::render('account/Index', $data);
    }
}
