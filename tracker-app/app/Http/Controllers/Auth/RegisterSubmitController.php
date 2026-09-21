<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Auth\GuardianAwareness;
use App\Mail\Auth\TrooperRegistered;
use App\Messages\Troopers\Commands\CreateTrooper;
use App\Messages\Troopers\Commands\Membership\CreateTrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use Exception;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Mail;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles the submission of the trooper registration form.
 *
 * This controller processes the complete registration flow by:
 * - Creating the trooper account with PENDING status
 * - Submitting pending organization join requests
 * - Linking OAuth accounts if applicable
 * - Sending confirmation email
 * - Redirecting to the thank you page
 */
class RegisterSubmitController extends Controller
{
    public function __construct(private readonly FlashMessageService $flash) {}

    /**
     * Handle the incoming registration request.
     *
     * Orchestrates the multi-step registration process including trooper creation,
     * organization join requests and email confirmation.
     * The newly created trooper will have PENDING status until approved by an admin.
     *
     * @param  RegisterRequest  $request  The validated registration form request
     * @return InertiaResponse|SymfonyResponse A redirect to the thank you page after successful registration
     */
    public function __invoke(RegisterRequest $request): InertiaResponse|SymfonyResponse
    {
        try
        {
            $trooper = $this->registerTrooper($request);

            Mail::to($trooper->email)->queue(new TrooperRegistered);

            if ($trooper->is_minor)
            {
                Mail::to($trooper->guardian->email)->queue(new GuardianAwareness);
            }

            dispatch(new SendTrooperRegisteredNotificationsJob($trooper));

            $this->flash->success('Request submitted successfully! You will receive an e-mail when your request is approved or denied.');

            return Inertia::location(route('auth.thank-you'));
        }
        catch (Exception $e)
        {
            return back()->withDanger($e->getMessage());
        }
    }

    private function registerTrooper(RegisterRequest $request): Trooper
    {
        return DB::transaction(function () use ($request) {
            $trooper = CreateTrooper::call($request);

            if ($trooper->membership_role != MembershipRole::HANDLER)
            {
                $this->registerTrooperOrganizations($trooper, $request);
            }

            return $trooper;
        });
    }

    private function registerTrooperOrganizations(Trooper $trooper, RegisterRequest $request): void
    {
        $organizations = $request->validated('organizations', []);

        foreach ($organizations as $primary_organization_id => $data)
        {
            $identifier = $data['identifier'] ?? null;
            $selected = $data['selected'] ?? false;
            $region_id = $data['region_id'] ?? null;
            $unit_id = $data['unit_id'] ?? null;

            if ($selected)
            {
                //  start with the primary organization as the default assignment
                //  if visitor we keep the primary organization as the assignment
                //  otherwise we resolve the organization based on region and unit
                $organization = Organization::findOrFail($primary_organization_id);

                if ($trooper->membership_role == MembershipRole::MEMBER)
                {
                    $organization = $this->resolveOrganization($organization, $region_id, $unit_id);
                }

                CreateTrooperRequest::call($trooper, $organization, $identifier);
            }
        }
    }

    private function resolveOrganization(Organization $organization, ?int $region_id, ?int $unit_id): Organization
    {
        $region = $organization->organizations()
            ->ofTypeRegions()
            ->firstWhere(Organization::ID, $region_id);

        if ($region->organizations()->count() == 0)
        {
            return $region;
        }

        return $region->organizations()
            ->ofTypeUnits()
            ->firstWhere(Organization::ID, $unit_id);
    }
}
